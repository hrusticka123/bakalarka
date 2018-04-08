<?php

function download($id, $user, $att)
{
    $config = require "./config.php";
    
    $Parser = new PhpMimeMailParser\Parser();

    $tempdir = $config['maildir']."/".$user."/".$id;
    $Parser->setPath($tempdir."/".$id);
    $Parser->saveAttachments($tempdir."/", $att);
    $file = $tempdir."/".$att;
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    while (ob_get_level()) {
        ob_end_clean();
    }
    readfile($file);
    unlink($file);
    exit;
}
?>