<?php

//login user
function login($username, $password)
{
    //open users db  
    $db = new SQLite3(datadir.'/usersdb');

    $sql = 'SELECT * FROM USERS';
    $ret = $db->query($sql);


    //check for valid credentials
    while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
        if ($username == $row['USER'] && $password == $row['PASS'])
        {
            //create login key
            $loginkey = hash("sha256",$username.$password.time());

            //add to db
            $dbkeys = new SQLite3(datadir.'/loginkeys');
            $stmt = $dbkeys->prepare('INSERT INTO LOGINKEYS (KEY, USER) VALUES (?, ?)');
            $stmt->bindValue(1, $loginkey, SQLITE3_TEXT);
            $stmt->bindValue(2, $username, SQLITE3_TEXT);
            $stmt->execute();
            $dbkeys->close();

            $db->close();
            return  '{ "success" : true, "loginkey" : "'.$loginkey.'"}';
        }
     }

     $db->close();
    return '{ "success" : false }';
}

?>