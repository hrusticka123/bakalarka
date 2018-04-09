<?php

function search($user,$input,$number)
{
    $input = strtolower($input);
    $input = preg_replace('/\s+/', '', $input);
    $queries = explode("and", $input);

    $data = new stdClass();
    $data->query = new stdClass();
    $data->query->bool = new stdClass();
    $data->query->bool->must = array();

    $wannabeintrash = false;

    foreach ($queries as $query)
    {
        $qData = explode(":",$query);
        if (count($qData) == 1)
        {   
            $multimatch = new stdClass();
            $multimatch->query_string->query = "*".unaccent($qData[0])."*";
            $multimatch->query_string->fields = ["subject","to","from","text","tag"];
            $data->query->bool->must[] = $multimatch; 
        }
        else
        {
            $searchfield = '';
            if ($qData[0] == "tag")
                $searchfield = translate($qData[1], $user);
            else
                $searchfield = $qData[1];
            $match = '{ "wildcard": { "'.$qData[0].'":  "*'.unaccent($searchfield).'*" }}';
            $match = json_decode($match);
            $data->query->bool->must[] = $match;
        }

        
        if ($query == "tag:trash")
            $wannabeintrash = true;
    }

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

    if ($response->hits->total > 0)
    {
        $return = new \stdClass();
        $return->success = true;
        $return->hitcount = $response->hits->total;
        $return->groups = array();

        $alreadyfoundreferences = array();

        foreach($response->hits->hits as $hit)
        {
            $isintrash = false;
            if(in_array($hit->_source->messageid,$alreadyfoundreferences))
                continue;

            $dataref = new stdClass();
            $dataref ->query = new stdClass();
            $dataref ->query->bool = new stdClass();
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