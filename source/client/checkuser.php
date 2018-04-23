<?php

function checkuser($received)
{
    require '../emails/mailparse/autoload.php';
    $config = require './config.php';

    $db = new SQLite3($config['datadir'].'/usersdb');
    $sql = 'SELECT * FROM USERS';
    $ret = $db->query($sql);

    $Parser = new PhpMimeMailParser\Parser();
    $Parser->setText($received);

    $receiveduser = $Parser->getHeader("to");

    $userfound = false;

    while($row = $ret->fetchArray(SQLITE3_ASSOC))
    {
        if ($row['USER'] == $receiveduser)
        {  
            $userfound = true;
            $hash = hash("sha256",$received);
            file_put_contents($config['maildir']."/".$receiveduser."/".$hash, $received);
            
            $data = array(
                "from" => $Parser->getHeader("from"),
                "to" => $Parser->getHeader("to"),
                "date" => $Parser->getHeader("date"),
                "subject" => $Parser->getHeader("subject"),
                "text" => $Parser->getMessageBody("text")
            );

            $elasticReq = curl_init();
                
            curl_setopt_array($elasticReq, array(
            CURLOPT_URL => "localhost:9200/".$receiveduser."/email/".$hash."?pretty",
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array( "content-type: application/json"),
            ));

            curl_exec($elasticReq);
            curl_close($elasticReq);
            
            break;
        }
    }
    $db->close();
}

?>