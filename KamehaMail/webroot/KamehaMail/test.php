<?php

$realtags = json_decode(getusertags($user));
    foreach ($realtags as $realtag)
    {
        $tagtext = strtolower($realtag->text);
        $tagtext = preg_replace('/\s+/', '', $tagtext);
        if ($usertag == $tagtext)
        {
            echo $realtag->search;
        }
    }
echo $usertag;

?>