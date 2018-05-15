<?php

function removeUser($user)
{   
    //open db with users
    $db = new SQLite3(datadir.'/usersdb');
    
    $sql = 'SELECT * FROM USERS';
    $ret = $db->query($sql);

    //check if user exists
    while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
        if ($user == $row['USER'])
        {
             //insert new user to db
            $stmt = $db->prepare('DELETE FROM USERS WHERE USER = ?');
            $stmt->bindValue(1, $user, SQLITE3_TEXT);
            $stmt->execute();

            $db->close();

            delete_directory(maildir.'/'.$username);

            //new index for elasticsearch
            $req = curl_init();
            
            curl_setopt_array($req, [
                CURLOPT_URL            => "http://localhost:9200/".$username,
                CURLOPT_CUSTOMREQUEST  => "DELETE",
                CURLOPT_RETURNTRANSFER => true
            ]);
            
            curl_exec($req);
            curl_close($req);
                
            return '{ "success" : true }';
        }
     }
     return 'No such user exists';
}


function delete_directory($dirname) 
{
    if (is_dir($dirname))
      $dir_handle = opendir($dirname);
    if (!$dir_handle)
        return false;
    while($file = readdir($dir_handle)) {
        if ($file != "." && $file != "..") {
            if (!is_dir($dirname."/".$file))
                    unlink($dirname."/".$file);
            else
                    delete_directory($dirname.'/'.$file);
        }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
}
?>