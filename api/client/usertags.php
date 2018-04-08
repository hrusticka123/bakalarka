<?php

function getusertags($user)
{
    $config = require "./config.php";
    return file_get_contents($config['maildir']."/".$user."/tags.txt");
}


function addusertag($user,$tag,$text)
{
    $config = require "./config.php";
    $currtags = json_decode(file_get_contents($config['maildir']."/".$user."/tags.txt"));
    
    $newtag = new stdClass();
    $newtag->icon = "label";
    $newtag->text = $text;
    $newtag->search = ($tag != '') ? $tag : makenewtag($currtags);
    $newtag->issearch = ($tag != '') ? "true" : "false";
    
    $currtags[] = $newtag;
    file_put_contents($config['maildir']."/".$user."/tags.txt", json_encode($currtags));
    return json_encode($issearch);


}

function adjustusertag($tagid, $user, $info)
{
    $config = require "./config.php";
    $currtags = json_decode(file_get_contents($config['maildir']."/".$user."/tags.txt"));
    $info = json_decode($info);
    
    foreach ($currtags as $currtag)
    {
        if ($currtag->search == $tagid)
        {
            $currtag->icon = $info->icon;
            $currtag->text = $info->text;
            $currtag->search = ($info->issearch == "true") ? $info->search : $currtag->search;
            break;
        }
    }

    file_put_contents($config['maildir']."/".$user."/tags.txt", json_encode($currtags));
}

function removeusertag($tag, $user)
{
    $config = require "./config.php";
    $currtags = json_decode(file_get_contents($config['maildir']."/".$user."/tags.txt"));
    
    $i = 0;
    foreach ($currtags as $currtag)
    {
        if ($currtag->search == $tag)
        {
            break;
        }
        $i++;
    }

    array_splice($currtags, $i, 1);

    file_put_contents($config['maildir']."/".$user."/tags.txt", json_encode($currtags));
}


function makenewtag($currtags)
{
    if (count($currtags) == 0)
        return "usertag0";
    else
    {
        $i = 0;
        foreach ($currtags as $currtag)
        {
            if ($i != intval(str_replace("usertag","",$currtag->search)))
                break;
            $i++;
        }
        return "usertag".$i;
    }
}
?>