<?php
//common redirecting API
//all calls to api folder go through htaccess
//api.php parses them and call a relevant php module 
//autoload path needs to be implemented manually here!

$autoload_path = "/path/to/autoload/";

require_once($autoload_path);
$parsedquery = explode('/', $_SERVER['QUERY_STRING']);

$user = '';
if ($_POST['key'])
{
    $user = json_decode(checklogged($_POST['key']));
    $user = $user->user;
}

switch ($parsedquery[0])
{
    case 'emails':
    {
         switch ($parsedquery[1])
         {
            case 'getmail':
            {
                echo getmail($_POST['ids'],$user);
            }
            break;
            case 'sendmail':
            {
                echo sendmail(json_encode($_POST['info']), $user);
            }
            break;
            case 'changetags':
            {
                echo changetags($_POST['ids'],$_POST['tag'],$user,$_POST['untag']);
            }
            break;
            case 'removemail':
            {
                echo removemail($_POST['id'],$user);
            }
            break;
            case 'attachment':
            {
                download($parsedquery[2],$parsedquery[3],$parsedquery[4]);
            }
            break;
            case 'upload':
            {  
                echo upload($_FILES, $parsedquery[2]);
            }
            break;
            case 'removeatts':
            {
                echo removeatts($_POST['atts'],$user,$_POST['hash']);
            }
            break;
         }
    }
    break;
    case 'elastic':
    {
        switch ($parsedquery[1])
        {
            case 'search':
                echo search($user,$_POST['query'],$_POST['number']);
                break;
            case 'updatetags':
                echo updatetags($_POST['tags'],$_POST['ids'],$user);
                break;
            case 'removemail':
            {
                echo removemailes($_POST['id'],$user);
            }
            break;
            case 'reindex':
            {
                echo reindex($_POST['file']);
                break;
            }
        }
    }
    break;
    case 'client':
    {
        switch ($parsedquery[1])
         {
            case 'login':
            {   
                echo login($_POST['username'], $_POST['password']);
            }
            break;
            case 'checklogged':
            {
                echo checklogged($_POST['loginkey']);
            }
            break;
            case 'logout':
            {
                echo logout($_POST['loginkey']);
            }
            break;
            case 'signup':
            {
                echo signup($_POST['username'],$_POST['password']);
            }
            break;
            case 'getusertags':
            {
                echo getusertags($user);
            }
            break;
            case 'addusertag':
            {
                echo addusertag($user, $_POST['tag'],$_POST['text']);
            }
            break;
            case 'removeusertag':
            {
                echo removeusertag($_POST['id'], $user);
            }
            break;
            case 'adjustusertag':
            {
                echo adjustusertag(intval($_POST['id']), $user, json_encode($_POST['info']));
            }
            break;
            case 'getmailer':
            {
                echo getmailer($user);
            }
            break;
            case 'setmailer':
            {
                setmailer($user,$_POST['mailer']);
            }
            break;
            case 'changepassword':
            {   
                echo changepassword($user,$_POST['password']);
            }
            break;
         }
    }
    break;
} 
?>
