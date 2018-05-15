<?php
//download attachment function
//gets email ID, owner of email and attachment ID
function download($id, $loginkey, $att)
{
    $user = '';
    $check = json_decode(checklogged($loginkey));
    if ($check->user)
        $user = $check->user;
    else
        exit;
    $Parser = new PhpMimeMailParser\Parser();
    //save the attachment to user-email specific folder
    $tempdir = maildir."/".$user."/".$id;
    $Parser->setPath($tempdir."/".$id);
    $Parser->saveAttachments($tempdir."/", $att);
    $file = $tempdir."/".$att;
    //set correct headers for download
        
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
    //download
    readfile($file);
    //remove attachment
    unlink($file);
    exit;
}
?>