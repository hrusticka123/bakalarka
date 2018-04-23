<?php
//temporarily uploads files for send
function upload($files,$user)
{  
    $config = require "./config.php";
    $return = new stdClass();
    $return->files = array();
    //we create hash for the files, which will be their directory
    $hash = makehash($files);
    $return->hash = $hash;
    $uploads_dir = $config['maildir']."/".$user."/".$hash;
    //create the directory
    mkdir($uploads_dir);
    
    //save the files
    foreach ($files as $file) {
        if ($file['error'] == 0) {
            $tmp_name = $file["tmp_name"];
            $name = $file["name"];

            move_uploaded_file($tmp_name, $uploads_dir."/".$name);

            $return->files[] = $name;
        }
        else
        {
            $return->success = false;
            return json_encode($return);
        }
    }

    $return->success = true;
    return json_encode($return);
}

function makehash()
{
    $hashstring = '';
    foreach ($files as $file) {
        if ($file['error'] == 0) {
            $hashstring .= $file["tmp_name"];
        }
    }
    return hash("sha256",$hashstring);
}
?>