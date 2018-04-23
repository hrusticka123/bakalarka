<?php

//email receive handler
//received email is stored and function call to handle it
$received = file_get_contents("php://stdin");

require(__DIR__.'/emails/decide.php');
require(__DIR__.'/emails/mailparse/autoload.php');
require(__DIR__.'/emails/saveMail.php');
require(__DIR__.'/elastic/saveMail.php');

decide($received);

?>
