<?php

function checklogged($loginkey)
{
    $config = require './config.php';
    //get login keys
    $db = new SQLite3($config['datadir'].'/loginkeys');
    $stmt = $db->prepare('SELECT * FROM LOGINKEYS WHERE KEY = ?');
    $stmt->bindValue(1, $loginkey, SQLITE3_TEXT);
    $ret = $stmt->execute();

    //check if it exists
    if ($row = $ret->fetchArray(SQLITE3_ASSOC))
    {
        $db->close(); 
        return '{ "success" : true, "user" : "'.$row['USER'].'"}';
    }
    $db->close();
    return '{ "success" : false }';
}
?>