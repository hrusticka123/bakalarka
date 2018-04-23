<?php

//hard email removal from database
function removemail($mailid, $user)
{
    $config = require "./config.php";

    $dir = $config['maildir']."/".$user."/".$mailid;
    $files = glob($dir."/*"); 

    foreach($files as $file){
      if(is_file($file))
        unlink($file);
    }
    rmdir($dir);
}

?>