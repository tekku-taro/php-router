<?php
require './vendor/autoload.php';

 $routes = array(
  "/etc/php5" => "php5",
  "/etc/php5/cli" => "cli",
  "/etc/php5/abc" => "abc",
  "/etc/php5/cli/:name" => "cli@name",
  "/etc/php5/cli/conf.d" => "cli@conf.d",
  "/etc/php5/cli/php.ini" => "cli@php.ini",
  "/etc/php5/cli/:id/show" => "id@show",
  "/etc/php5/cli/:id/create" => "id@create",
  "/etc/php5/cli/man/readme" => "man@readme.ini",
  "/etc/php5/conf.d" => "conf.d",
  "/etc/php5/conf.d/mysqli.ini" => "conf.d@mysqli.ini",
  "/etc/php5/conf.d/curl.ini" => "conf.d@curl.ini",
  "/etc/php5/apache2" => "apache2",
  "/etc/php5/apache2/conf.d" => "apache2@conf.d",
  "/etc/php5/apache2/php.ini" => "apache2@php.ini"
 );


$router = new Taro\Routing\Router;
$router->makeTree($routes);

print_r($router->showTree());


// Array
// (
//     [etc] => Array
//         (
//             [php5] => Array
//                 (
//                     [cli] => Array
//                         (
//                             [__params] => Array
//                                 (
//                                     [:id] => Array
//                                         (
//                                             [show] => id@show
//                                             [create] => id@create
//                                         )

//                                     [:name] => cli@name
//                                 )

//                             [man] => Array
//                                 (
//                                     [readme] => man@readme.ini
//                                 )

//                             [conf.d] => cli@conf.d
//                             [php.ini] => cli@php.ini
//                             [__node_val] => cli
//                         )

//                     [conf.d] => Array
//                         (
//                             [mysqli.ini] => conf.d@mysqli.ini
//                             [curl.ini] => conf.d@curl.ini
//                             [__node_val] => conf.d
//                         )

//                     [apache2] => Array
//                         (
//                             [conf.d] => apache2@conf.d
//                             [php.ini] => apache2@php.ini
//                             [__node_val] => apache2
//                         )

//                     [abc] => abc
//                     [__node_val] => php5
//                 )

//         )

// )
