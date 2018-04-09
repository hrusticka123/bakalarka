<?php

function changepassword($user,$password)
{
    $config = require './config.php';
    $db = new SQLite3($config['datadir'].'/usersdb');

    $stmt = $db->prepare('UPDATE USERS SET PASS = :pass WHERE USER = :user');
    $stmt->bindParam(':user', $user);
    $stmt->bindParam(':pass', $password);
    $stmt->execute();
    $db->close();
}

?>