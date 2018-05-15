<?php

//get current mailer
function getmailer($user)
{
    return file_get_contents(maildir."/".$user."/mailer.txt");
}
//set new mailer
function setmailer($user, $mailer)
{
    file_put_contents(maildir."/".$user."/mailer.txt", $mailer);
}

?>
