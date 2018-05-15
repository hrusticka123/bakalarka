<?php

//changes user password
function changepassword($key,$password)
{
    $user = checklogged($key);
    $db = new SQLite3(datadir.'/usersdb');

    $stmt = $db->prepare('UPDATE USERS SET PASS = :pass WHERE USER = :user');
    $stmt->bindParam(':user', $user);
    $stmt->bindParam(':pass', $password);
    $stmt->execute();
    $db->close();
}

?>