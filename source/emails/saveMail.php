<?php

//saves mail to database
function savemail($user,$hash,$mail,$tags)
{
    $tags = json_encode($tags);

    $maildir = maildir."/".$user."/".$hash;
    mkdir($maildir);
    file_put_contents($maildir."/".$hash, $mail);
    file_put_contents($maildir."/".$hash.".tags", $tags);
}

?>