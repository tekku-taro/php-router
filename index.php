<?php
require './vendor/autoload.php';


$urls = explode("\n", file_get_contents('urls.csv')) ;
$samples = explode("\n", file_get_contents('samples.csv')) ;
shuffle($samples);
// print_r($urls);
foreach ($urls as $key => $url) {
    $routes2[] =["GET",$url, 'val' . $key];
}
$timeStart = microtime(true);
$router = new Taro\Routing\Router(true);
// $router->setRoutes($routes2);
$router->registerRoutes();

$timeMid = microtime(true);

foreach ($samples as $key => $sample) {
    $results[] = ($router->match($sample));
}
$timeEnd = microtime(true);


// print_r($router->showTrees());

print 'ルーティング作成時間：' . ($timeMid - $timeStart) . ' 秒' . PHP_EOL;
print '実行時間：' . ($timeEnd - $timeMid) . ' 秒' . PHP_EOL;

$content = '';
foreach ($results as $key => $result) {
    $params = '';
    foreach ($result['params'] as $key => $value) {
        $params .= $key . '=' . $value . '  ';
    }
    $content .= 'URL:' . $result['url'] . ' CALLBACK:' .   $result['callback'] . ' PARAMS:' . $params . PHP_EOL;
}

file_put_contents('result.csv', $content);
