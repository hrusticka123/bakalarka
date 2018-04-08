<?php

function removemailes($id, $user)
{

    $req = curl_init();
    
    curl_setopt_array($req, [
        CURLOPT_URL            => "localhost:9200/".$user."/email/".$id."?refresh=true&pretty&pretty",
        CURLOPT_CUSTOMREQUEST  => "DELETE",
        CURLOPT_RETURNTRANSFER => true,
    ]);
    
    $response = json_decode(curl_exec($req));

    curl_close($req);

    return json_encode($response);
}

?>