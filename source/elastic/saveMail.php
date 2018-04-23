<?php
//saves mail to elastic database
function savemailtoes($parsedData, $user, $hash)
{
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
//for simpler and faster grouping, all references are push in elasticsearch to all email in group
    if ($parsedData['references'])
       updatereferences($parsedData['references'],$user,$parsedData['messageid']);
}

function updatereferences($refs,$user,$newid)
{
    //find each reference and add it new ID as reference
    $elasticReq = curl_init();
    foreach($refs as $ref)
    {
        $data = ' { "script": { "lang": "painless", "source": "ctx._source.references.add(params.ref)", "params": { "ref": "'.$newid.'" } }, "query": { "term": { "messageid": "'.$ref.'" } } } ';
        curl_setopt_array($elasticReq, array(
        CURLOPT_URL => 'localhost:9200/'.$user.'/email/_update_by_query?refresh=true&pretty',
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array( "content-type: application/json"),
        CURLOPT_RETURNTRANSFER => true,
        ));
        curl_exec($elasticReq);
    }
    curl_close($elasticReq);
}
?>