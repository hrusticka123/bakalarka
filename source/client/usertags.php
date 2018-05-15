<?php
//functions for work with user tag files
//get current users tags
function getusertags($user)
{
    return file_get_contents(maildir."/".$user."/tags.txt");
}

//add new tag
function addusertag($user,$tag,$text)
{
    $currtags = json_decode(file_get_contents(maildir."/".$user."/tags.txt"));
    
    $newtag = new stdClass();
    $newtag->icon = "label";
    $newtag->text = $text;
    $newtag->search = ($tag != '') ? $tag : makenewtag($currtags);
    $newtag->issearch = ($tag != '') ? "true" : "false";
    
    $currtags[] = $newtag;
    file_put_contents(maildir."/".$user."/tags.txt", json_encode($currtags));
    return $newtag->search;
}

//change existing tag
function adjustusertag($tagid, $user, $info)
{
    $currtags = json_decode(file_get_contents(maildir."/".$user."/tags.txt"));
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

    file_put_contents(maildir."/".$user."/tags.txt", json_encode($currtags));
}

//remove user tag
function removeusertag($tag, $user)
{
    $currtags = json_decode(file_get_contents(maildir."/".$user."/tags.txt"));
    
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

    file_put_contents(maildir."/".$user."/tags.txt", json_encode($currtags));
}

//create tag
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