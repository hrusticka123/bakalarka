<?php
//search according to the given query in elasticsearch database
//user to look at, input query and number of messages to be retrieved
function search($user,$input,$number)
{
    //adjust the query for simpler processing
    $input = strtolower($input);
    $input = preg_replace('/\s+/', '', $input);
    //split query by AND
    $queries = explode("and", $input);

    $data = new stdClass();
    $data->query = new stdClass();
    $data->query->bool = new stdClass();
    $data->query->bool->must = array();

    //for filtering the trash messages from standard search
    //except for the case when we want to look at the trashed messages
    $wannabeintrash = false;

    foreach ($queries as $query)
    {
        //each query is split by ':'
        $qData = explode(":",$query);
        //if split returned only one element, the field was not specified and we want to search in all of them
        if (count($qData) == 1)
        {   
            $multimatch = new stdClass();
            $multimatch->query_string->query = "*".unaccent($qData[0])."*";
            $multimatch->query_string->fields = ["subject","to","from","text","tag"];
            $data->query->bool->must[] = $multimatch; 
        }
        else
        {
            //search field was specified, search in it
            $searchfield = '';
            if ($qData[0] == "tag")
                $searchfield = translate($qData[1], $user);
            else
                $searchfield = $qData[1];
            $match = '{ "wildcard": { "'.$qData[0].'":  "*'.unaccent($searchfield).'*" }}';
            $match = json_decode($match);
            $data->query->bool->must[] = $match;
        }

        //we are looking in trash
        if ($query == "tag:trash")
            $wannabeintrash = true;
    }

    //sorting by date
    $data->sort = json_decode( '{ "date" : "desc" }');

    $req = curl_init();
    
    curl_setopt_array($req, [
        CURLOPT_URL            => "http://localhost:9200/".$user."/email/_search?size=".$number."&pretty",
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    
    $response = json_decode(curl_exec($req));

    //if some results were found
    if ($response->hits->total > 0)
    {
        $return = new \stdClass();
        $return->success = true;
        $return->hitcount = $response->hits->total;
        $return->groups = array();

        //looking for all possible references to found mails
        $alreadyfoundreferences = array();

        foreach($response->hits->hits as $hit)
        {
            $isintrash = false;
            //check for duplicates
            if(in_array($hit->_source->messageid,$alreadyfoundreferences))
                continue;

            $dataref = new stdClass();
            $dataref ->query = new stdClass();
            $dataref ->query->bool = new stdClass();
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
                if (in_array("trash",$hit->_source->tag))
                {
                    $isintrash = true;
                }

                $group[] = $hit->_id;
                $alreadyfoundreferences[] = $hit->_source->messageid;
            }
            if ($isintrash && !$wannabeintrash)
                continue;
            $return->groups[] = $group;
        }

        curl_close($req);
        return json_encode($return);
    }
    else
        return '{ "success" : false }';
}

//translates from query tag to real tag as it is saved
function translate($usertag, $user)
{
    $realtags = json_decode(getusertags($user));
    foreach ($realtags as $realtag)
    {
        $tagtext = strtolower($realtag->text);
        $tagtext = preg_replace('/\s+/', '', $tagtext);
        if ($usertag == $tagtext)
            return $realtag->search;
    }
    return $usertag;
}
?>