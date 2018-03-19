<?php

session_start();

if(isset($_SESSION['username']))
{
    header('Location: emails.php');
}

else if(!isset($_POST['username'])) {
    session_destroy();
    echo ("<script LANGUAGE='JavaScript'>
    window.alert('Could not login');
    window.location.href='index.php';
    </script>");
}
else {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
    $inbox = @imap_open($hostname,$username,$password);

    if (!imap_errors() && !imap_alerts())
    {
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        imap_close($inbox);
        echo ' { "success" : true } ';
    }
    else
        echo ' { "success" : false } ';
}
?>