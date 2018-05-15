<?php
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


//translates from query tag to real tag as it is saved
function translate($usertag, $user)
{
    $realtags = json_decode(getusertags($user));
    foreach ($realtags as $realtag)
    {
        $tagtext = strtolower($realtag->text);
        $tagtext = preg_replace('/\s+/', '', $tagtext);
        if ($usertag == $tagtext)
            return $realtag->search;
    }
    return $usertag;
}
?>