<?php

function removeatts($atts)
{
    $config = require "./config.php";
    $dir = $config['maildir']."/temp";
    $files = glob($dir."/*"); 

    foreach ($atts as $att)
    {
        foreach($files as $file){
            if(is_file($file) && basename($file) == $att)
                unlink($file);

        }
    }
    return json_encode($files);
}

?>