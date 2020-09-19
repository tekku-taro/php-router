<?php

use Taro\Routing\Router;

require './vendor/autoload.php';

$performTest = new Performance();

$performTest->testPerformance();

$performTest->testPerformanceWithCache();


$router = new Router(false);
$router->loadRoutes();

$router->showTrees();
$url = '/products';

$result = $router->match($url, 'GET');

print_r($result);
