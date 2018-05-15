<?php

//simply change tags for mail group in its tag file
function changetags($ids, $tag,$user,$untag)
{
    $tags = array();
    $changed = false;
    $i = 0;
    foreach ($ids as $id)
    {
        $tags[$i] = json_decode(file_get_contents(maildir."/".$user."/".$id."/".$id.".tags"));

        $key = array_search($tag, $tags[$i]);

        //if we want untag the group and we found the tag, untag it
        if ($key !== false && $untag == "true") 
        {
            $changed = true;
            array_splice($tags[$i], $key, 1);
        }
        //if we want to tag group and we didnt find the tag, tag it
        else if($key === false && $untag == "false")
        {
            $changed = true;
            $tags[$i][] = $tag;
        }
    
        file_put_contents(maildir."/".$user."/".$id."/".$id.".tags", json_encode($tags[$i]));
        $i++;
    }

    $return = new \stdClass();
    $return->tags = $tags;
    $return->changed = $changed;
    return json_encode($return);
}

?>