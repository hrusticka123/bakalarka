<?php
//check if user is logged
//meaning, if the given loginkey is legit
function checklogged($loginkey)
{
    //get login keys
    $db = new SQLite3(datadir.'/loginkeys');
    $stmt = $db->prepare('SELECT * FROM LOGINKEYS WHERE KEY = ?');
    $stmt->bindValue(1, $loginkey, SQLITE3_TEXT);
    $ret = $stmt->execute();

    //check if it exists
    if ($row = $ret->fetchArray(SQLITE3_ASSOC))
    {
        $db->close(); 
        return '{ "user" : "'.$row['USER'].'"}';
    }
    $db->close();
    return '{ "success" : false }';
}
?>