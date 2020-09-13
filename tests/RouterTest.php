<?php
require_once "vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use Taro\Routing\Router;

class RouterTest extends TestCase
{
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
            'params' => []
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
            'params' => []
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
        
        $url='/etc/php5/cli/man/readme';
        $expected = [
            'callback'  => 'man@readme.ini',
            'url' => $url,
            'params' => []
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));
        
        $url='/etc/php5/apache2';
        $expected = [
            'callback'  => 'apache2',
            'url' => $url,
            'params' => []
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
            'params' => []
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));

        $url='/etc/php5/cli/man/readme/234';
        $expected = [
            'callback'  => null,
            'url' => $url,
            'params' => []
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
            ]
        ];
        $this->assertEquals($expected, $this->router->match($url, $method));

        $url = '/etc/php5/abc/admin';
        $expected = [
            'callback'  => 'role.php',
            'url' => $url,
            'params' => [
                'role'=>'admin'
            ]
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
            ]
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
            ]
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
            ]
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
            ]
        ];
        $this->assertEquals($expected, $router->match($url, $method));
    }
}
