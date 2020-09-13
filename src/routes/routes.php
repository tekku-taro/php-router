<?php

$router->get('/products', 'Products@index');
$router->post('/products', 'Products@store');
$router->put('/products/:id', 'Products@update');
$router->delete('/products/:id', 'Products@delete');

$router->setRoutes([
    ["GET","/" , 'index.html'],
    ["GET","/etc/php5" , "php5"],
    ["GET","/etc/php5/cli" , "cli"],
    ["GET","/etc/php5/abc" , "abc"],
    ["GET","/etc/php5/abc/:role" , "role.php"],
    ["GET","/etc/php5/cli/man/readme" , "man@readme.ini"],
    ["GET","/etc/php5/cli/:id/show" , "id@show"],
]);
