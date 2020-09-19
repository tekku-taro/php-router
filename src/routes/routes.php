<?php
// 課題：グルーピング機能を追加する


$router->get('/products', 'Products@index',['middleware'=>['auth','api']]);
$router->post('/products', 'Products@store');
$router->put('/products/:id', 'Products@update');
$router->delete('/products/:id', 'Products@delete');
$router->get('/users/:id?', '?route');
$router->controller('/tasks','TaskController');
$router->setRoutes([
    ["GET","/" , 'index.html'],
    ["GET","/etc/php5" , "php5"],
    ["GET","/etc/php5/cli" , "cli"],
    ["GET","/etc/php5/abc" , "abc"],
    ["GET","/etc/php5/abc/:role" , "role.php"],
    ["GET","/etc/php5/cli/man/readme" , "man@readme.ini"],
    ["GET","/etc/php5/cli/:id/show" , "id@show"],
]);
