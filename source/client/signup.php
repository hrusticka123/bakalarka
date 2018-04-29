<?php

function signup($username, $password)
{   
    $config = require './config.php';
    $username = $username.'@'.$config['domain'];
    //check for empty
    if ($username == '' || $password == '')
    {
        return '{ "success" : false, "reason" : "Username and password must be non empty" }';
    }

    //open db with users
    $db = new SQLite3($config['datadir'].'/usersdb');
    
    $sql = 'SELECT * FROM USERS';
    $ret = $db->query($sql);

    //check if user exists
    while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
        if ($username == $row['USER'])
            return '{ "success" : false, "reason" : "Username already taken" }';
     }

     //insert new user to db
    $stmt = $db->prepare('INSERT INTO USERS (USER, PASS) VALUES (?, ?)');
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $stmt->bindValue(2, $password, SQLITE3_TEXT);
    $stmt->execute();

    $db->close();

    //create folder for emails
    mkdir($config['maildir'].'/'.$username);

    //initialise tags
    file_put_contents($config['maildir'].'/'.$username."/tags.txt","[]");
    //initialise mailer
    file_put_contents($config['maildir'].'/'.$username."/mailer.txt","");
    
    //new index for elasticsearch
    $req = curl_init();
    
    curl_setopt_array($req, [
        CURLOPT_URL            => "http://localhost:9200/".$username."?pretty",
        CURLOPT_CUSTOMREQUEST  => "PUT",
        CURLOPT_POSTFIELDS     => ' { "settings" : {  "number_of_shards" : 1, "number_of_replicas" : 1 },  "mappings": { "email": { "properties": { "date": {  "type": "date", "format" : "E, d MMM Y H:m:s Z" }, "messageid" : {"type" : "keyword"  },  "references" : {"type" : "keyword" } } } } } ',
        CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    curl_exec($req);
    curl_close($req);
        
    return '{ "success" : true }';
}

?>