<?php

function logout($loginkey)
{
    //get login keys
    $db = new SQLite3(datadir.'/loginkeys');
    $stmt = $db->prepare('SELECT * FROM LOGINKEYS WHERE KEY = ?');
    $stmt->bindValue(1, $loginkey, SQLITE3_TEXT);
    $ret = $stmt->execute();

    //check if it exists
    if ($ret->fetchArray())
    {
        //remove from login keys
        $stmt = $db->prepare('DELETE FROM LOGINKEYS WHERE KEY = ?');
        $stmt->bindValue(1, $loginkey, SQLITE3_TEXT);
        $stmt->execute();

        $db->close();
        return '{ "success" : true }';
    }

    $db->close();
    return '{ "success" : false }';
}
?>