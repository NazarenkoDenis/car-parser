<?php

error_reporting(-1);
ini_set("display_errors", "1");
ini_set("log_errors", 1);
ini_set("error_log", "./php-error.log");

require('CarParser.php');
$carParser = new CarParser;

$startTime = time();
var_dump($carParser->getCarData('https://www.olx.ua/uk/obyavlenie/hyundai-getz-IDJvrYB.html'));
$processTime = time() - $startTime;

echo  'Processed for: ' . $processTime . ' s';