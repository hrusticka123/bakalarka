<?php
//functions for work with user tag files
//get current users tags
function getusertags($user)
{
    $db = new SQLite3(maildir."/".$user."/tags");
    $toret = array();
    $sql = 'SELECT * FROM TAGS';
    $ret = $db->query($sql);
    while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
        $toret[] = array_change_key_case($row);
     }
    $db->close();
    return json_encode($toret);
}

//add new tag
function addusertag($user,$tag,$text)
{
    $db = new SQLite3(maildir."/".$user."/tags");
    
    $sql = 'SELECT MAX(ID) FROM TAGS';
    $result = $db->query($sql);
    $max = 0;

    $result = $result->fetchArray();
    if (is_null($result['MAX(ID)']) === false)
        $max = $result['MAX(ID)'] + 1;

    $newsearch = ($tag != '') ? $tag : makenewtag($max);
    $stmt = $db->prepare('INSERT INTO TAGS (ID,SEARCH, TEXT, ICON, ISSEARCH) VALUES (?,?,?,?,?)');
    $stmt->bindValue(1, $max, SQLITE3_INTEGER);
    $stmt->bindValue(2, $newsearch, SQLITE3_TEXT);
    $stmt->bindValue(3, $text, SQLITE3_TEXT);
    $stmt->bindValue(4, "label", SQLITE3_TEXT);
    $stmt->bindValue(5, ($tag != '') ? 1 : 0, SQLITE3_INTEGER);
    $stmt->execute();

    $db->close();

    return $newsearch;
}

//change existing tag
function adjustusertag($tagid, $user, $info)
{
    $info = json_decode($info);
    $db = new SQLite3(maildir."/".$user."/tags");

    $stmt = $db->prepare('UPDATE TAGS SET ICON = ?, TEXT = ? WHERE ID = ?');
    $stmt->bindValue(1, $info->icon, SQLITE3_TEXT);
    $stmt->bindValue(2, $info->text, SQLITE3_TEXT);
    $stmt->bindValue(3, $tagid, SQLITE3_INTEGER);
    $stmt->execute();

    if (intval($info->issearch) == 1)
    {
        $stmt = $db->prepare('UPDATE TAGS SET SEARCH = ? WHERE ID = ?');
        $stmt->bindValue(1, $info->search, SQLITE3_TEXT);
        $stmt->bindValue(2, $tagid, SQLITE3_INTEGER);
        $stmt->execute();
    }

    $db->close();
}

//remove user tag
function removeusertag($tag, $user)
{
    $db = new SQLite3(maildir."/".$user."/tags");
    
    $stmt = $db->prepare('DELETE FROM TAGS WHERE ID = ?');
    $stmt->bindValue(1, $tag, SQLITE3_INTEGER);
    $stmt->execute();

    $db->close();
}

//create tag
function makenewtag($max)
{
    if ($max == 0)
        return "usertag0";
    else
    {
        return "usertag".$max;
    }
}
?>