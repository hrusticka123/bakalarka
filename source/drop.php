<?php

function drop($user, $tag)
{
    $received = file_get_contents("php://stdin");

    $Parser = new PhpMimeMailParser\Parser();
    $Parser->setText($received);

    $hash = hash("sha256",$received);

    $usertags = json_decode(getusertags($user));
    $newtag = '';
    if (($newtag = translate($tag, $user)) == $tag)
        $newtag = addusertag($user,'',$tag);
    $newtag = [$newtag];
    savemail($user,$hash,$received,$newtag);

    $data = array(
        "from" => unaccent($Parser->getHeader("from")),
        "to" => unaccent($Parser->getHeader("to")),
        "date" => explode(" (", $Parser->getHeader("date"))[0],
        "subject" => unaccent($Parser->getHeader("subject")),
        "text" => unaccent($Parser->getMessageBody("text")),
        "messageid" => substr(htmlspecialchars($Parser->getHeader("message-id")),4,-4),
        "tag" => $newtag,
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

    savemailtoes($data, $user,$hash);

    echo "Mail ".$hash." was indexed\n";
}
?>
