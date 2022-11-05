# Php Router

Webアプリケーションでクライアントから送られたリクエストを元に送信先のControllerなどの値を返す、php製URLルーティング用のクラス。

## 特徴

- 簡潔で分かりやすいルーティング定義方法
- ルーティングファイルにまとめて記述
- キャッシュ機能により、ルーティング情報の登録をキャッシュでき、次回以降の実行速度を向上できる
- 内部ではルーティング情報から木構造を生成し、実行処理の向上を実現

## 実行速度

実行環境によりますが、自分の環境(CPU４コア 3.50Ghz RAM 8GB)では以下のようになりました。

5回実行した平均値を測定

| 登録ルート数 | ルーティング作成時間( ms ) | 処理時間( ms ) |
| ------------ | -------------------------- | -------------- |
| 30           | 0.920                      | 0.0359         |
| 300          | 8.48                       | 0.0398         |
| 2500         | 56.6                       | 0.0432         |



## 使い方

## ルーティングの設定

ルーティング設定用ファイルを作成して（routes/routes.phpを参照）、実行時に `loadRoutes` メソッドにファイルへのパスを指定します。このファイルに以下のようなルート定義文を、`$router`インスタンスのメソッドの形で記述します。なお、`$router`インスタンスはファイル読み込み時にRouterクラスから渡されます。

### リクエストメソッドと同名メソッドで各ルートを定義

```php
// メソッド名('URLのパス', '登録したい値', [オプション配列])
$router->post('/products', 'Products@store');

// パラメータは :パラメータ名 で指定
$router->put('/products/:id', 'Products@update');
$router->delete('/products/:id', 'Products@delete');

// 第３引数のオプションにmiddlewareを指定
$router->get('/products', 'Products@index', ['middleware'=>['auth','api']]);
```

### URL最後のパラメータをオプションに設定

```php
// パラメータ名の末尾に ? を付けると、オプションになる
$router->get('/users/:id?', 'routeWithOptionParam');
```

### controllerメソッドでCRUDの７つのメソッドへのルートを一括登録

```php
$router->controller('/tasks', 'TasksController');
// 以下のルートを登録
// GET     /tasks              TasksController@index
// GET     /tasks/:tasks       TasksController@show
// GET     /tasks/create       TasksController@create
// GET     /tasks/:tasks/edit  TasksController@edit
// POST    /tasks              TasksController@store
// PUT     /tasks/:tasks       TasksController@update
// DELETE  /tasks/:tasks       TasksController@delete    
```

### 配列でルートを一括登録

```php
$router->setRoutes([
    ["GET","/" , 'index.html'],
    ["GET","/etc/php5/abc/:role" , "role.php"],
    ["GET","/etc/php5/cli/man/readme" , "man@readme.ini", ['middleware'=>'auth']],
]);
```

### groupメソッドで共通処理を適用

#### groupパラメータ

- `prefix`：　内部のルート定義に共通するURLを指定する
- `middleware`：　ルートに適用するミドルウェア名

```php
// group([グループパラメータ], function($router){  ここにルート定義文を記述   })
$router->group(['prefix'=>'order/:order_no','middleware'=>'admin'], function ($router) {
    $router->get('/shipping', 'Shipping@index'); // url => order/:order_no/shipping

    // group は入れ子にできる
    $router->group(['prefix'=>'payment'], function ($router) {
      $router->delete('/credit/:code', 'Credit@delete'); // url => order/:order_no/payment/credit/:code
    });
});
```

## ルーティングの実行

1. Routerクラスのインスタンスを作成します。
2. **設定ファイルへのパス**を渡して、ルート設定ファイルを読み込みます。
3. クラスの`match`メソッドにURLとリクエストメソッドを渡します。
4. 結果が配列で取得できます。

```php
$router = new Router();
$router->loadRoutes([routes.phpへのパス]);
// $router->showTrees(); 作成したルーティング木を表示

$url = '/products';
$result = $router->match($url, 'GET');

print_r($result);
```

## ルーティング結果

`match`メソッドは以下の構造の戻り値を返します。

```php
[
    'callback'  => '登録されている値',
    'url' => $url,
    'params' => [
        'id' => 111, // ルートパラメータがある時、パラメーター名をキーとした配列を返す
    ]
    'options' => [
        'middleware'=>['web']
    ]
]
```

## ルーティング表の表示

`showTable`メソッドによって、現在登録されているルーティング表を表示します。

```php
$router->showTable();

// ルーティング表表示
|Method |Url                                           |Action                    |Options
|DELETE |/tasks/:tasks                                 |TaskController@delete     |middleware=web
|   GET |/tasks/:tasks/edit                            |TaskController@edit       |middleware=web
|   GET |/tasks/:tasks                                 |TaskController@show       |middleware=web
|   GET |/tasks/create                                 |TaskController@create     |middleware=web
|   GET |/tasks                                        |TaskController@index      |middleware=web
|  POST |/tasks                                        |TaskController@store      |middleware=web
|   PUT |/tasks/:tasks                                 |TaskController@update     |middleware=web
```



## ライセンス (License)

**Php Router**は[MIT license](https://opensource.org/licenses/MIT)のもとで公開されています。

**Php Router** is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).