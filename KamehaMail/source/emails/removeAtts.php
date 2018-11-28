<?php

//deletes attachments after upload is done
function removeatts($atts,$user,$hash)
{
    $dir = maildir."/".$user."/".$hash;
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