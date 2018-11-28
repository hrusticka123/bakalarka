<?php

//hard email removal from database
function removemail($hash, $user)
{
    $path = getPathFromHash($hash);
    $dir = maildir."/".$user."/".$path;
    $files = glob($dir."/*"); 

    foreach($files as $file){
      if(is_file($file))
        unlink($file);
    }
    rmdir($dir);
}

?>