<?php

//saves mail to database
function savemail($user,$hash,$mail,$tags)
{

    $config = require(dirname(__DIR__).'/config.php');
    $tags = json_encode($tags);

    $maildir = $config['maildir']."/".$user."/".$hash;
    mkdir($maildir);
    file_put_contents($maildir."/".$hash, $mail);
    file_put_contents($maildir."/".$hash.".tags", $tags);
}

?>