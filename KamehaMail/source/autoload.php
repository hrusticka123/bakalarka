<?php

//requires necessary files for run
require_once "config.php";

$files = glob(source.'/client/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
$files = glob(source.'/emails/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
$files = glob(source.'/elastic/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
$files = glob(source.'/emails/mailsend/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}
require_once(source.'/emails/mailparse/autoload.php' );

?>