<?php

function decide($received)
{
    $config = require(dirname(__DIR__).'/config.php');

    $Parser = new PhpMimeMailParser\Parser();
    $Parser->setText($received);

    $hash = hash("sha256",$received);
    if (strpos($Parser->getHeader("from"), "@hruska.blesmrt.cf") !== false)
    {
        $sentuser = $Parser->getHeader("from");
        $tags = ["sent"];
        $user = pureuser($sentuser);
        savemail($user ,$hash,$received,$tags);

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
        return savemailtoes($data, $user,$hash);
    }

    $receivedusers = explode(",",$Parser->getHeader("to"));
    foreach ($receivedusers as $receiveduser)
    {
        $receiveduser = pureuser($receiveduser);
        if (strpos($receiveduser, "@hruska.blesmrt.cf") !== false)
        {
            $db = new SQLite3($config['datadir'].'/usersdb');

            $sql = 'SELECT * FROM USERS';
            $ret = $db->query($sql);

            $userfound = false;
            
            while($row = $ret->fetchArray(SQLITE3_ASSOC))
            {
                if ($row['USER'] == $receiveduser)
                {  
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

function pureuser($receiveduser)
{
    $user = preg_replace('/\s+/', '', $receiveduser);
    if (count(explode('<',$user)) > 1)
        return str_replace(">","",explode('<',$user)[1]);
    else
        return $user;
}

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

function unaccent($string)
{
    setlocale(LC_CTYPE, 'en_US.UTF8');
    return iconv("UTF-8", "ASCII//TRANSLIT", $string);
}
?>
