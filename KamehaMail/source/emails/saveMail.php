<?php

//saves mail to database
function savemail($user,$hash,$mail,$tags)
{
    $tags = json_encode($tags);
    $path = getPathFromHash($hash);
    $maildir = maildir."/".$user."/".$path;
    mkdir($maildir,0777,true);
    file_put_contents($maildir."/".$hash, $mail);
    file_put_contents($maildir."/".$hash.".tags", $tags);
}

?>