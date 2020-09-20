<?php

use Taro\Routing\Router;

require './vendor/autoload.php';

// パフォーマンステスト
$performTest = new Performance();

$performTest->testPerformance();

$performTest->testPerformanceWithCache();


// 以下、Routerクラスの実行見本
// $router = new Router(false);
// $router->loadRoutes();

// $router->showTrees();
// $url = '/products';

// $result = $router->match($url, 'GET');

// print_r($result);
