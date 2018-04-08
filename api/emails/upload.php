<?php

function upload($files)
{  
    $config = require "./config.php";
    $return = new stdClass();
    $return->files = array();
    $uploads_dir = $config['maildir']."/temp";

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
?>