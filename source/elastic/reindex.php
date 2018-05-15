<?php

function reindex($user)
{
    $hasharray = explode(PHP_EOL,file_get_contents("php://stdin"));
    $results = array();

    foreach ($hasharray as $hash)
    {
        if ($hash == '') continue;
        $file_path = maildir."/".$user."/".$hash."/".$hash;
        if (file_exists($file_path) === false)
        {
            $results[] = "File ".$hash." does not exist";
            continue;
        }

        $Parser = new PhpMimeMailParser\Parser();
        $Parser->setPath($file_path);

        $tags = file_get_contents($file_path.'.tags');
        if ($tags === false)
            $tags = ["inbox"];
        else
            $tags = json_decode($tags);

        //data for elasticsearch
        $data = array(
            "from" => unaccent($Parser->getHeader("from")),
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
        echo "Reindexed ".$hash;
    }
}
?>
