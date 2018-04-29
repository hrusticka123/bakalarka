<?php

//function that recognizes if the message was sent or recieved
//parses the email and saves necessary parts
function decide($received)
{
    $config = require(dirname(__DIR__).'/config.php');

    $Parser = new PhpMimeMailParser\Parser();
    $Parser->setText($received);

    //mail hash
    $hash = hash("sha256",$received);
    //from header is from our users, parse it as sent
    if (strpos($Parser->getHeader("from"), $config['domain']) !== false)
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
            "date" => $Parser->getHeader("date"),
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
        if (strpos($receiveduser, $config['domain']) !== false)
        {
            //database of users
            //mail was sent to us but we need to check if received user exist
            $db = new SQLite3($config['datadir'].'/usersdb');

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
                        "date" => $Parser->getHeader("date"),
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

//removes all unwanted symbols, such as '<>' or whitespaces, and the mailer name
function pureuser($receiveduser)
{
    $user = preg_replace('/\s+/', '', $receiveduser);
    if (count(explode('<',$user)) > 1)
        return str_replace(">","",explode('<',$user)[1]);
    else
        return $user;
}

//refs without '<>' and whitespaces
function purerefs($references)
{
    $references = htmlspecialchars($references);
    $references = str_replace("&gt;", "",$references);
    $parts = explode("&lt;",$references);
    array_splice($parts,0,1);
    $newrefs = array();
    foreach ($parts as $part)
    {
        $newrefs[] = preg_replace('/\s+/', '', $part);
    }
    return $newrefs;
}

//remove all language accents, works fine on czech and slovak
//its used for elasticsearch storage
function unaccent($string)
{
    setlocale(LC_CTYPE, 'en_US.UTF8');
    return iconv("UTF-8", "ASCII//TRANSLIT", $string);
}
?>
