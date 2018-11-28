<?php

//function that recognizes if the message was sent or received
//parses the email and saves necessary parts
function decide($received)
{
    $Parser = new PhpMimeMailParser\Parser();
    $Parser->setText($received);

    //mail hash
    $hash = hash("sha256",$received);
    //from header is from our users, parse it as sent
    if (strpos($Parser->getHeader("from"), domain) !== false)
    {
        $sentuser = $Parser->getHeader("from");
        $tags = ["sent"];
        $user = pureuser($sentuser);
        //saves email to database as it was received
        savemail($user ,$hash,$received,$tags);

        //data for elasticsearch
        $data = array(
            "from" => unaccent($sentuser) ,
            "to" => unaccent($Parser->getHeader("to")),
            "date" => explode(" (", $Parser->getHeader("date"))[0], 
            "subject" => unaccent($Parser->getHeader("subject")),
            "text" => unaccent($Parser->getMessageBody("text")),
            "messageid" => substr(htmlspecialchars($Parser->getHeader("message-id")),4,-4),
            "tag" => $tags,
            "references" => array()
        );

        if ($Parser->getHeader("references"))
        {
            $refs = purerefs($Parser->getHeader("references"));
            foreach ($refs as $reference)
            {
                $data["references"][] = $reference;
            }
        }
        //saving to elastic
        savemailtoes($data, $user,$hash);
    }

    //for all users that sent the email
    //we need to check all possible senders and find our users among them
    $receivedusers = explode(",",$Parser->getHeader("to"));
    foreach ($receivedusers as $receiveduser)
    {
        $receiveduser = pureuser($receiveduser);
        if (strpos($receiveduser, domain) !== false)
        {
            //database of users
            //mail was sent to us but we need to check if received user exist
            $db = new SQLite3(datadir.'/usersdb');

            $sql = 'SELECT * FROM USERS';
            $ret = $db->query($sql);

            $userfound = false;
            
            while($row = $ret->fetchArray(SQLITE3_ASSOC))
            {
                //we found our user
                if ($row['USER'] == $receiveduser)
                {  
                    //this is almost same as the part above
                    $userfound = true;
                    $hash = hash("sha256",$received);

                    $tags = ["inbox","unread"];
                    savemail($receiveduser,$hash,$received,$tags);

                    $data = array(
                        "from" => unaccent($Parser->getHeader("from")),
                        "to" => unaccent($receiveduser),
                        "date" => explode(" (", $Parser->getHeader("date"))[0],
                        "subject" => unaccent($Parser->getHeader("subject")),
                        "text" => unaccent($Parser->getMessageBody("text")),
                        "messageid" => substr(htmlspecialchars($Parser->getHeader("message-id")),4,-4),
                        "tag" => $tags,
                        "references" => array()
                    );
            
                    if ($Parser->getHeader("references"))
                    {
                        $refs = purerefs($Parser->getHeader("references"));
                        foreach ($refs as $reference)
                        {
                            $data["references"][] = $reference;
                        }
                    }

                    savemailtoes($data, $receiveduser,$hash);
                    break;
                }
            }
            $db->close();
        }
    }
}

?>
