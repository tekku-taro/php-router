<?php
require_once "vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use Taro\Routing\Router;

class RouterTest extends TestCase
{
    protected $options = [
        'middleware' => ['web']
    ];

    public function setUp():void
    {
    }

    protected function setupTree()
    {
        $routes = [
            ["GET","/" , 'index.html'],
            ["GET","/etc/php5" , "php5"],
            ["GET","/etc/php5/cli" , "cli"],
            ["GET","/etc/php5/abc" , "abc"],
            ["GET","/etc/php5/abc/:role" , "role.php"],
            ["GET","/etc/php5/cli/:name" , "cli@name"],
            ["GET","/etc/php5/cli/conf.d" , "cli@conf.d"],
            ["GET","/etc/php5/cli/php.ini" , "cli@php.ini"],
            ["GET","/etc/php5/cli/:id/show" , "id@show"],
            ["GET","/etc/php5/cli/:id/create" , "id@create"],
            ["GET","/etc/php5/cli/man/readme" , "man@readme.ini"],
            ["GET","/etc/php5/conf.d" , "conf.d"],
            ["GET","/etc/php5/conf.d/mysqli.ini" , "conf.d@mysqli.ini"],
            ["GET","/etc/php5/conf.d/curl.ini" , "conf.d@curl.ini"],
            ["GET","/etc/php5/apache2" , "apache2"],
            ["GET","/etc/php5/apache2/conf.d" , "apache2@conf.d"],
            ["GET","/etc/php5/apache2/php.ini" , "apache2@php.ini"],
        ];
        $this->router = new Router(false);
        $this->router->setRoutes($routes);
        $this->router->registerRoutes();
    }

    public function testMatchWrongMethod()
    {
        $this->setupTree();

        $method = 'POST';
        
        $url='/etc/php5/abc';
        $expected = [
            'callback'  => null,
            'url' => $url,
            'params' => [],
            'options'=>[]
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
    }

    public function testMatch()
    {
        $this->setupTree();

        $method = 'GET';
        
        $url='/etc/php5/abc';
        $expected = [
            'callback'  => 'abc',
            'url' => $url,
            'params' => [],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
        
        $url='/etc/php5/cli/man/readme';
        $expected = [
            'callback'  => 'man@readme.ini',
            'url' => $url,
            'params' => [],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
        
        $url='/etc/php5/apache2';
        $expected = [
            'callback'  => 'apache2',
            'url' => $url,
            'params' => [],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
    }

    public function testNotMatched()
    {
        $this->setupTree();

        $method = 'GET';
        
        $url='/etc/ph45/abc';
        $expected = [
            'callback'  => null,
            'url' => $url,
            'params' => [],
            'options'=>[]
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));

        $url='/etc/php5/cli/man/readme/234';
        $expected = [
            'callback'  => null,
            'url' => $url,
            'params' => [],
            'options'=>[]
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
    }

    public function testMatchedWithParams()
    {
        $this->setupTree();

        $method = 'GET';
        
        $url = '/etc/php5/cli/123/show';
        $expected = [
            'callback'  => 'id@show',
            'url' => $url,
            'params' => [
                'id'=>123
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));

        $url = '/etc/php5/abc/admin';
        $expected = [
            'callback'  => 'role.php',
            'url' => $url,
            'params' => [
                'role'=>'admin'
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
    }

    public function testGet()
    {
        $router = new Router;

        $method = 'GET';

        $path='/user/:id/create';
        $callback = 'user@create';
        $router->get($path, $callback);
        $router->registerRoutes();

        $url = '/user/50/create';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'id'=>50
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }

    public function testPost()
    {
        $router = new Router;

        $method = 'POST';

        $path='/user/:id/store';
        $callback = 'user@store';
        $router->post($path, $callback);
        $router->registerRoutes();

        $url = '/user/50/store';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'id'=>50
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }



    public function testPut()
    {
        $router = new Router;

        $method = 'PUT';

        $path='/user/:id/update';
        $callback = 'user@update';
        $router->put($path, $callback);
        $router->registerRoutes();

        $url = '/user/50/update';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'id'=>50
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }

    public function testDelete()
    {
        $router = new Router;

        $method = 'DELETE';

        $path='/user/:id/delete';
        $callback = 'user@delete';
        $router->delete($path, $callback);
        $router->registerRoutes();
        $url = '/user/50/delete';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'id'=>50
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }


    
    public function testOptionParam()
    {
        $router = new Router;

        $method = 'GET';

        $path='/user/:id?';
        $callback = '?route';
        $router->get($path, $callback);
        $router->registerRoutes();

        $url = '/user/50';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'id'=>50
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));

        $url = '/user';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }
    
    public function testController()
    {
        $router = new Router;



        $path='/tasks';
        $controller = 'TasksController';
        $router->controller($path, $controller);
        $router->registerRoutes();

        $method = 'GET';
        $url = '/tasks/3';
        $callback = $controller . '@show';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'tasks'=>3
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
        
        $method = 'GET';
        $url = '/tasks';
        $callback = $controller . '@index';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));
        

        $method = 'GET';
        $url = '/tasks/4/edit';
        $callback = $controller . '@edit';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'tasks'=>4
            ],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));

        $method = 'GET';
        $url = '/tasks/create';
        $callback = $controller . '@create';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [],
            'options'=>$this->options
        ];
        $this->assertEquals($expected, $router->match($url, $method));


        $method = 'POST';
        $url = '/tasks';
        $callback = $controller . '@store';
        $expected = [
        'callback'  => $callback,
        'url' => $url,
        'params' => [],
        'options'=>$this->options
    ];
        $this->assertEquals($expected, $router->match($url, $method));


        $method = 'PUT';
        $url = '/tasks/3';
        $callback = $controller . '@update';
        $expected = [
        'callback'  => $callback,
        'url' => $url,
        'params' => [
            'tasks'=>3
        ],
        'options'=>$this->options
    ];
        $this->assertEquals($expected, $router->match($url, $method));

        $method = 'DELETE';
        $url = '/tasks/3';
        $callback = $controller . '@delete';
        $expected = [
        'callback'  => $callback,
        'url' => $url,
        'params' => [
            'tasks'=>3
        ],
        'options'=>$this->options
    ];
        $this->assertEquals($expected, $router->match($url, $method));
    }

    public function testGroup()
    {
        $router = new Router;
        $router->group(['prefix'=>'order/:order_no','middleware'=>['admin']], function ($router) {
            $router->get('/shipping', 'Shipping@index');
            $router->group(['prefix'=>'payment'], function ($router) {
                $router->delete('/credit/:code', 'Credit@delete');
            });
        });
        $router->registerRoutes();

        $method = 'GET';
        $url = '/order/10/shipping';
        $callback = 'Shipping@index';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'order_no'=>10
            ],
            'options'=>[
                'middleware'=>['admin']
            ]
        ];
        $this->assertEquals($expected, $router->match($url, $method));

        $method = 'DELETE';
        $url = '/order/10/payment/credit/wojf23';
        $callback = 'Credit@delete';
        $expected = [
            'callback'  => $callback,
            'url' => $url,
            'params' => [
                'order_no'=>10,
                'code'=>'wojf23',
            ],
            'options'=>[
                'middleware'=>['admin']
            ]
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }
}
