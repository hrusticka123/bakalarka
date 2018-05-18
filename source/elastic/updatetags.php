<?php

//change tags in elasticsearch
//function only gets the new changed tags from the database and rewrites the current ones
//parameters: list of tags, list of mail IDs and username
function updatetags($tags,$ids,$user)
{
    $i = 0;
    foreach ($ids as $id)
    {
        $data = '{ "doc" : { "tag" : '.json_encode($tags[$i]).' } }';
        $req = curl_init();

        curl_setopt_array($req, [
            CURLOPT_URL            => "http://localhost:9200/".$user."/email/".$id."/_update?refresh=true&pretty",
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        $response = json_decode(curl_exec($req));
    
        curl_close($req);
    
        $i++;
    }

}

?>