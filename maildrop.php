<?php

$received = file_get_contents("php://stdin");

require(__DIR__.'/api/emails/decide.php');
require(__DIR__.'/api/emails/mailparse/autoload.php');
require(__DIR__.'/api/emails/saveMail.php');
require(__DIR__.'/api/elastic/saveMail.php');

decide($received);

?>
