<?php

function changetags($ids, $tag,$user,$untag)
{
    $config = require './config.php';
    $tags = array();

    $i = 0;
    foreach ($ids as $id)
    {
        $tags[$i] = json_decode(file_get_contents($config['maildir']."/".$user."/".$id."/".$id.".tags"));

        $key = array_search($tag, $tags[$i]);

        if ($key !== false && $untag == "true") 
        {
            array_splice($tags[$i], $key, 1);
        }
        else if($key === false && $untag == "false")
        {
            $tags[$i][] = $tag;
        }
    
        file_put_contents($config['maildir']."/".$user."/".$id."/".$id.".tags", json_encode($tags[$i]));
        $i++;
    }

    return json_encode($tags);
}

?>