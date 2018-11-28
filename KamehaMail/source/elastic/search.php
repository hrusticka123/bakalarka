<?php
//search according to the given query in elasticsearch database
//parameters: user to look at, input query and number of messages to be retrieved
function search($user,$input,$number)
{
    //adjust the query for simpler processing
    $input = strtolower($input);
    $input = preg_replace('/\s+/', '', $input);
    //split query by AND
    $queries = explode("and", $input);

    $return = new \stdClass();
    $data = new stdClass();
    $data->query = new stdClass();

    //for filtering the trash messages from standard search
    //except for the case when we want to look at the trashed messages
    $wannabeintrash = false;
    $wannabeinarchive = true;

    foreach ($queries as $query)
    {
        //each query is split by ':'
        $qData = explode(":",$query);
        //if split returned only one element, the field was not specified and we want to search in all of them
        if (count($qData) == 1)
        {   
            $multimatch = new stdClass();
            $multimatch->query_string->query = "*".unaccent($qData[0])."*";
            $multimatch->query_string->fields = ["subject","to","from","text"];
            $data->query->bool->must[] = $multimatch; 
        }
        else
        {
            //date filtering
            if($qData[0] == "time")
            {
                if(($str = getTime($qData[1])) !== false)
                {
                    $range->range = json_decode('{ "date" : { '.$str.', "format" : "dd.MM.yyyy||dd/MM/yyyy" } }');
                    $data->query->bool->must[] = $range;
                }
                else
                {
                    $return->success = false;
                    $return->message = "Wrong date format, d.m.y or d/m/y expected";
                    return json_encode($return);
                }
            }
            else
            {
                //search field was specified, search in it
                $match = '';
                if ($qData[0] == "tag")
                    $match = json_decode('{ "term": { "tag":  "'.translate($qData[1], $user).'" }}');
                else
                    $match = json_decode('{ "wildcard": { "'.$qData[0].'":  "*'.unaccent($qData[1]).'*" }}');
                $data->query->bool->must[] = $match;
            }
        }

        //trashed wanted
        if ($query == "tag:trash")
            $wannabeintrash = true;
        //archived wanted
        else if ($query == "tag:inbox" || $query == "tag:sent")
            $wannabeinarchive = false;
    }

    //sorting by date
    $data->sort = json_decode( '{ "date" : "desc" }');

    $req = curl_init();
    
    curl_setopt_array($req, [
        CURLOPT_URL            => "http://localhost:9200/".$user."/email/_search?size=".$number."&pretty",
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_POSTFIELDS     => json_encode($data,JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    
    $response = json_decode(curl_exec($req));

    //if some results were found
    if ($response->hits->total > 0)
    {
        $hitcount = 0;
        $return->success = true;
        $took = $response->took;
        $return->groups = array();

        //looking for all possible references to found mails
        $alreadyfoundreferences = array();

        foreach($response->hits->hits as $hit)
        {
            $skip = false;
            //check for duplicates
            if(in_array($hit->_id,$alreadyfoundreferences))
                continue;
            else if (empty($hit->_source->references))
            {
                if (!((in_array("trash",$hit->_source->tag) && !$wannabeintrash) || (in_array("archive",$hit->_source->tag) && !$wannabeinarchive)))
                {
                    $group = array();
                    $group[] = $hit->_id;
                    $return->groups[] = $group;
                    $hitcount++;
                }
                continue;
            }

            $dataref = new \stdClass();
            $dataref->query = new \stdClass();
            $dataref->query->bool = new \stdClass();
            //look for messages with references to hit message or the hit message itself
            $dataref ->query->bool= json_decode(' { "should" : [ { "term" : { "messageid" : "'.$hit->_source->messageid.'" } }, { "term" : { "references" : "'.$hit->_source->messageid.'" } } ], "minimum_should_match": 1 }');
            $dataref ->sort = json_decode( '{ "date" : "asc" }');
    
            curl_setopt_array($req, [
                CURLOPT_URL            => "http://localhost:9200/".$user."/email/_search?size=10000&pretty",
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_POSTFIELDS     => json_encode($dataref),
                CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
                CURLOPT_RETURNTRANSFER => true,
            ]);
            
            $group = array();
            $responseref = json_decode(curl_exec($req));

            //grouping of emails by references
            foreach ($responseref->hits->hits as $hit)
            {
                if ((in_array("trash",$hit->_source->tag) && !$wannabeintrash) || (in_array("archive",$hit->_source->tag) && !$wannabeinarchive))
                {
                    $skip = true;
                    break;
                }
                $took += $responseref->took;
                $hitcount++;
                $group[] = $hit->_id;
                $alreadyfoundreferences[] = $hit->_id;
            }
            if ($skip)
                continue;
            $return->groups[] = $group;
        }

        curl_close($req);
        $return->message = $hitcount." results (".$took."ms)";
        return json_encode($return);
    }
    else
    {
        $return->success = false;
        $return->message = "No match found";
        return json_encode($return);
    }
}

//parses time
function getTime($data)
{
    if (($pos = strpos($data,"<")) !== false)
    {
        $time = substr($data,$pos+1);
        if (DateTime::createFromFormat( "d.m.Y", $time) === false && DateTime::createFromFormat( "d/m/Y", $time) === false) return false;
        else return '"lte" : "'.$time.'"';
    }
    else if (($pos = strpos($data,">")) !== false)
    {
        $time = substr($data,$pos+1);
        if (DateTime::createFromFormat( "d.m.Y", $time) === false && DateTime::createFromFormat( "d/m/Y", $time) === false) return false;
        else return '"gte" : "'.$time.'"';
    }
    else if (strpos($data,"-") !== false)
    {
        $time = explode("-",$data);
        if ((DateTime::createFromFormat( "d.m.Y", $time[0]) === false && DateTime::createFromFormat( "d/m/Y", $time)[0] === false)
         || (DateTime::createFromFormat( "d.m.Y", $time[1]) === false && DateTime::createFromFormat( "d/m/Y", $time)[1] === false)) return false;
        else return '"lte" : "'.$time[1].'", "gte" : "'.$time[0].'"';
    }
    else
    {
        if (DateTime::createFromFormat( "d.m.Y", $data) === false && DateTime::createFromFormat( "d/m/Y", $data) === false) return false;
        else return '"lte" : "'.$data.'", "gte" : "'.$data.'"';
    }
}

?>