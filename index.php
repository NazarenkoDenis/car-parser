<?php

error_reporting(-1);
ini_set("display_errors", "1");
ini_set("log_errors", 1);
ini_set("error_log", "./php-error.log");

require('CarParser.php');
$carParser = new CarParser;
var_dump($carParser->parse());