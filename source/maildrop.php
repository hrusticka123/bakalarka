<?php

//email receive handler
//received email is stored and function call to handle it
$received = file_get_contents("php://stdin");

require_once(__DIR__.'/autoload.php');

decide($received);

?>
