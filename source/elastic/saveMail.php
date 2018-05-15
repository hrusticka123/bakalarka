<?php
//saves mail to elastic database
function savemailtoes($parsedData, $user, $hash)
{
    //for simpler and faster grouping, all references are push in elasticsearch to all email in group
    if (empty($parsedData['references']) === false)
        updateOthersRefs($parsedData['references'],$user,$parsedData['messageid']);
   
    $parsedData['references'] = updateOwnRefs($parsedData['references'], $user, $parsedData['messageid']);
    
    $elasticReq = curl_init();
    
    curl_setopt_array($elasticReq, array(
        CURLOPT_URL => "localhost:9200/".$user."/email/".$hash."?pretty",
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($parsedData),
        CURLOPT_HTTPHEADER => array( "content-type: application/json"),
        CURLOPT_RETURNTRANSFER => true,
    ));

    curl_exec($elasticReq);
    curl_close($elasticReq);
}

function updateOthersRefs($refs,$user,$newid)
{
    //find each reference and add it new ID as reference
    $elasticReq = curl_init();
    foreach($refs as $ref)
    {
        $data = ' { "script": { "lang": "painless", "source": "ctx._source.references.add(params.ref)", "params": { "ref": "'.$newid.'" } }, "query": { "bool" : { "must" : { "term": { "messageid": "'.$ref.'" } }, "must_not" : { "term" : { "references" : "'.$newid.'" } } } } } ';
        curl_setopt_array($elasticReq, array(
        CURLOPT_URL => 'localhost:9200/'.$user.'/email/_update_by_query?pretty',
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array( "content-type: application/json"),
        CURLOPT_RETURNTRANSFER => true,
        ));
        curl_exec($elasticReq);
    }
    curl_close($elasticReq);
}

function updateOwnRefs($refs,$user,$id)
{
    $elasticReq = curl_init();

    $data = ' { "query" :{ "term": { "references": "'.$id.'" } } }';
    curl_setopt_array($elasticReq, array(
    CURLOPT_URL => 'localhost:9200/'.$user.'/email/_search?pretty',
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => array( "content-type: application/json"),
    CURLOPT_RETURNTRANSFER => true,
    ));
    $response = json_decode(curl_exec($elasticReq));
    if ($response->hits->total > 0)
    { 
        foreach ($response->hits->hits as $hit)
        {
            if (in_array($hit->_source->messageid,$refs) === false)
                $refs[] = $hit->_source->messageid;
        }
    }
    curl_close($elasticReq);

    return $refs;
}
?>