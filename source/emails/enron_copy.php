<?php

function copy_dir($user)
{
    $config = require('./config.php');
    $user = $user.'@'.$config['domain'];
    $folders = glob('/var/www/hruska.blesmrt.cf/source/'.$user.'/*');

    foreach($folders as $folder) {
        $files = glob($folder.'/*');

        if (basename($folder) != "inbox" && basename($folder) != "sent")
            $addedtag = addusertag($user,'',basename($folder));

        foreach($files as $file)
        {
            if (is_dir($file))
            {
                continue;
            }
        
            $Parser = new PhpMimeMailParser\Parser();
            $Parser->setPath($file);
            $received = file_get_contents($file);
            //mail hash
            $hash = hash("sha256",$received);
            //from header is from our users, parse it as sent
            if (basename($folder) == "inbox" || basename($folder) == "sent")
                $tags = [basename($folder)];
            else
                $tags = [$addedtag];
            //saves email to database as it was received
            savemail($user ,$hash,$received,$tags);

            //data for elasticsearch
            $data = array(
                "from" => $Parser->getHeader("from"),
                "to" => unaccent($Parser->getHeader("to")),
                "date" => substr($Parser->getHeader("date"), 0, strpos($Parser->getHeader("date"), " (")),
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
    }
    return "all done";
}

?>
