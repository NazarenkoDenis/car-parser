<?php

error_reporting(-1);
ini_set("display_errors", "1");
ini_set("log_errors", 1);
ini_set("error_log", "./php-error.log");

require('CarParser.php');
$carParser = new CarParser;

$startTime = time();
//var_dump($carParser->getCarData('https://www.olx.ua/uk/obyavlenie/hyundai-getz-IDJvrYB.html'));
var_dump($carParser->getCarData('https://www.olx.ua/uk/obyavlenie/srochno-prodam-chevrolet-aveo-t250-1-5-IDJfom4.html'));
//var_dump($carParser->getCarLinks());
//var_dump($carParser->getPreparedCars());
$processTime = time() - $startTime;

echo  'Processed for: ' . $processTime . ' s';