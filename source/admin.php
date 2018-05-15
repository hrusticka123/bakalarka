<?php
require_once(__DIR__."/config.php");
require_once(__DIR__."/emails/help.php");

switch($argv[1])
{
    case 'reindex':
    { 
        require_once(__DIR__."/elastic/reindex.php");
        require_once(__DIR__."/elastic/saveMail.php");
        require_once(__DIR__."/emails/mailparse/autoload.php");

        echo reindex($argv[2]);
    }
    break;
    case 'drop':
    {
        require_once(__DIR__."/drop.php");
        require_once(__DIR__."/emails/mailparse/autoload.php");
        require_once(__DIR__."/emails/saveMail.php");
        require_once(__DIR__."/elastic/saveMail.php");
        require_once(__DIR__."/client/usertags.php");

        drop($argv[3], $argv[2]);
    }
    break;
    case 'remove_mail':
    {
        require_once(__DIR__."/emails/removeMail.php");
        require_once(__DIR__."/elastic/removeMail.php");

        $hasharray = explode(PHP_EOL,file_get_contents("php://stdin"));
    
        foreach ($hasharray as $hash)
        {
            removemailes($hash, $argv[2])
            removemail($hash, $argv[2]);
        }
    }
    break;
    case 'remove_user':
    {
        require_once(__DIR__."/client/removeUser.php");

        echo removeUser($argv[2]);
    }
    break;
    case 'add_user':
    {
        require_once(__DIR__."/client/signup.php");

        signup($argv[2], $argv[3]);
    }
    break;
}

?>