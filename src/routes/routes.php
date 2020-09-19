<?php
/**
 * ルーティング設定用のファイル
 * $routerインスタンスを使って、定義文を作成
 * インスタンスは作成する必要なし
 *
 */

// groupメソッドで共通処理を適用
$router->group(['prefix'=>'order/:order_no','middleware'=>'admin'], function ($router) {
    $router->get('/shipping', 'Shipping@index');
    $router->post('/shipping', 'Shipping@store');

    $router->group(['prefix'=>'payment'], function ($router) {
        $router->delete('/credit/:code', 'Credit@delete');
    });
});

// リクエストメソッドと同名メソッドで各ルートを定義
$router->get('/products', 'Products@index', ['middleware'=>['auth','api']]);
$router->post('/products', 'Products@store');
$router->put('/products/:id', 'Products@update');
$router->delete('/products/:id', 'Products@delete');
$router->get('/users/:id?', '?route');

// controllerメソッドでCRUDの７つのメソッドへのルートを一括登録
$router->controller('/tasks', 'TaskController');

// 配列でルートを一括登録
$router->setRoutes([
    ["GET","/" , 'index.html'],
    ["GET","/etc/php5" , "php5"],
    ["GET","/etc/php5/cli" , "cli"],
    ["GET","/etc/php5/abc" , "abc"],
    ["GET","/etc/php5/abc/:role" , "role.php"],
    ["GET","/etc/php5/cli/man/readme" , "man@readme.ini"],
    ["GET","/etc/php5/cli/:id/show" , "id@show"],
]);
