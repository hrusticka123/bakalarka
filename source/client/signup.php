<?php

function signup($username, $password)
{   
    //check for empty
    if ($username == '' || $password == '')
    {
        return '{ "success" : false, "reason" : "Username and password must be non empty" }';
    }

    //open db with users
    $db = new SQLite3(datadir.'/usersdb');
    
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
    mkdir(maildir.'/'.$username);

    $db = new SQLite3(maildir.'/'.$username.'/tags');
    $sql ='CREATE TABLE TAGS
      (ID INT PRIMARY KEY,
      SEARCH  TEXT,
      TEXT     TEXT,
      ICON     TEXT,
      ISSEARCH INTEGER);';

    $db->exec($sql);
    $db->close();

    //initialise mailer
    file_put_contents(maildir.'/'.$username."/mailer.txt","");
    
    //new index for elasticsearch
    $req = curl_init();
    
    curl_setopt_array($req, [
        CURLOPT_URL            => "http://localhost:9200/".$username."?pretty",
        CURLOPT_CUSTOMREQUEST  => "PUT",
        CURLOPT_POSTFIELDS     => ' { "settings" : {  "number_of_shards" : 2, "number_of_replicas" : 2 },  "mappings": { "email": { "properties": { "date": {  "type": "date", "format" : "E, d MMM Y H:m:s Z" }, "messageid" : {"type" : "keyword"  },  "references" : {"type" : "keyword" },  "tag" : {"type" : "keyword" } } } } } ',
        CURLOPT_HTTPHEADER     => [ "Content-Type: application/json" ],
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    curl_exec($req);
    curl_close($req);
        
    return '{ "success" : true }';
}

?>