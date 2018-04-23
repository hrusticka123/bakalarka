<?php

//requires necessary files for run
$config = require_once "config.php";

$files = glob($config['sourcedir'].'/client/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
$files = glob($config['sourcedir'].'/emails/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
$files = glob($config['sourcedir'].'/elastic/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
$files = glob($config['sourcedir'].'/emails/mailsend/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
require_once( $config['sourcedir'].'/emails/mailparse/autoload.php' );

?>