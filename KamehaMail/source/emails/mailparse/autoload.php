<?php

//custom autoload for parsing component

$files = glob(__DIR__.'/Contracts/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}

$files = glob(__DIR__.'/*.php', GLOB_BRACE);
foreach($files as $file) {
  require_once ($file);
}

?>