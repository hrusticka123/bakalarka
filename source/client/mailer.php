<?php

//get current mailer
function getmailer($user)
{
    $config = require './config.php';
    return file_get_contents($config['maildir']."/".$user."/mailer.txt");
}
//set new mailer
function setmailer($user, $mailer)
{
    $config = require './config.php';
    file_put_contents($config['maildir']."/".$user."/mailer.txt", $mailer);
}

?>
