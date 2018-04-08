
<?php 
require 'autoload.php';
$parsedquery = explode('/', $_SERVER['QUERY_STRING']);

switch ($parsedquery[0])
{
    case 'emails':
    {
         switch ($parsedquery[1])
         {
            case 'getmail':
            {
                echo getmail($_POST['ids'],$_POST['user']);
            }
            break;
            case 'sendmail':
            {
                echo sendmail(json_encode($_POST['info']));
            }
            break;
            case 'changetags':
            {
                echo changetags($_POST['ids'],$_POST['tag'],$_POST['user'],$_POST['untag']);
            }
            break;
            case 'removemail':
            {
                echo removemail($_POST['id'],$_POST['user']);
            }
            break;
            case 'attachment':
            {
                download($parsedquery[2],$parsedquery[3],$parsedquery[4]);
            }
            break;
            case 'upload':
            {   
                echo upload($_FILES);
            }
            break;
            case 'removeatts':
            {   
                echo removeatts($_POST['atts']);
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
                echo search($_POST['user'],$_POST['query'],$_POST['number']);
                break;
            case 'updatetags':
                echo updatetags($_POST['tags'],$_POST['ids'],$_POST['user']);
                break;
            case 'removemail':
            {
                echo removemailes($_POST['id'],$_POST['user']);
            }
            break;
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
                echo getusertags($_POST['user']);
            }
            break;
            case 'addusertag':
            {
                addusertag($_POST['user'], $_POST['tag'],$_POST['text']);
            }
            break;
            case 'removeusertag':
            {
                echo removeusertag($_POST['id'], $_POST['user']);
            }
            break;
            case 'adjustusertag':
            {
                echo adjustusertag($_POST['id'], $_POST['user'],json_encode($_POST['info']));
            }
            break;
         }
    }
    break;
} 
?>
