<?php

//admin interface
//callable using php shell function
require_once(__DIR__."/config.php");
require_once(__DIR__."/emails/help.php");

//first argument is a call type
switch($argv[1])
{
    //reindexes selected mails
    //second argument is username, a list of hashes is expected on standard input
    case 'reindex':
    { 
        require_once(__DIR__."/elastic/reindex.php");
        require_once(__DIR__."/elastic/saveMail.php");
        require_once(__DIR__."/emails/mailparse/autoload.php");

        echo reindex($argv[2]);
    }
    break;
    //drops mail for user
    //second argument is username, third tag, mail content is expected on standard input
    case 'drop':
    {
        require_once(__DIR__."/drop.php");
        require_once(__DIR__."/emails/mailparse/autoload.php");
        require_once(__DIR__."/emails/saveMail.php");
        require_once(__DIR__."/elastic/saveMail.php");
        require_once(__DIR__."/client/usertags.php");

        echo drop($argv[2], $argv[3]);
    }
    break;
    //removes selected emails
    //second argument is username, list of hashes is expected on standard input
    case 'remove_mail':
    {
        require_once(__DIR__."/emails/removeMail.php");
        require_once(__DIR__."/elastic/removeMail.php");

        $hasharray = explode(PHP_EOL,file_get_contents("php://stdin"));
    
        foreach ($hasharray as $hash)
        {
            removemailes($hash, $argv[2]);
            removemail($hash, $argv[2]);
        }
    }
    break;
    //removes user
    //second argument is username
    case 'remove_user':
    {
        require_once(__DIR__."/client/removeUser.php");

        echo removeUser($argv[2]);
    }
    break;
    //adds new user
    //second argument is username, third is password
    case 'add_user':
    {
        require_once(__DIR__."/client/signup.php");

        signup($argv[2], $argv[3]);
    }
    break;
}

?>