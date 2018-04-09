<?php

function removeatts($atts,$user,$hash)
{
    $config = require "./config.php";
    $dir = $config['maildir']."/".$user."/".$hash;
    $files = glob($dir."/*"); 

    foreach ($atts as $att)
    {
        foreach($files as $file){
            if(is_file($file) && basename($file) == $att)
                unlink($file);

        }
    }
    rmdir($dir);
    return json_encode($files);
}

?>