<?php
namespace Taro\Routing;

class Router
{
    /**
     * ルーティング用の木構造のルート配列
     *
     * @var array
     */
    protected $map = [];

    /**
     * ルートパラメータ配列
     *
     * @var array
     */
    protected $params = [];

    protected $routes = [];


    protected $routesPath;
    protected $cachePath;
    protected $useCache;

    public function __construct($useCache = true, $routesPath = __DIR__ .  '/../routes/routes.php', $cachePath = __DIR__ .  '/../routes/cache')
    {
        $this->routesPath = $routesPath;
        $this->cachePath = $cachePath . '/cache_routes.cache';
        $this->useCache = $useCache;

        if ($this->useCache) {
            $this->checkCache();
        } else {
            $this->loadRoutes();
        }
    }

    public function loadRoutes()
    {
        $routing = function ($router) {
            include $this->routesPath;
        };

        $routing($this);
        $this->registerRoutes();
    }

    protected function checkCache()
    {
        if (file_exists($this->routesPath)) {
            $lastModified = filemtime($this->routesPath);
        } else {
            return;
        }

        if (file_exists($this->cachePath)) {
            $lastCached = filemtime($this->cachePath);

            if ($lastCached && ($lastCached > $lastModified)) {
                $this->loadCache();
                return;
            }
        }


        $this->loadRoutes();
    }

    protected function saveCache()
    {
        $json = json_encode($this->map);

        file_put_contents($this->cachePath, $json);
    }

    protected function loadCache()
    {
        $data = file_get_contents($this->cachePath);
        if (!empty($data)) {
            $array = json_decode($data, true);
            $this->map = $array;
        }
    }

    public function showTrees()
    {
        print_r($this->map);
    }

    public function clearTrees()
    {
        $this->map = [];
    }


    public function get($url, $callback)
    {
        list($key, $value) =  $this->makeRoute('GET', $url, $callback);
        $this->routes[$key][] = $value;
    }
    
    public function post($url, $callback)
    {
        list($key, $value) =  $this->makeRoute('POST', $url, $callback);
        $this->routes[$key][] = $value;
    }
    
    public function put($url, $callback)
    {
        list($key, $value) =  $this->makeRoute('PUT', $url, $callback);
        $this->routes[$key][] = $value;
    }
    
    public function delete($url, $callback)
    {
        list($key, $value) =  $this->makeRoute('DELETE', $url, $callback);
        $this->routes[$key][] = $value;
    }
    
    public function registerRoutes()
    {
        // print_r($this->routes);
        $this->makeTrees($this->routes);
        $this->routes = [];

        if ($this->useCache) {
            $this->saveCache();
        }
    }

    /**
     * urlとリクエストメソッドで登録された値を返す
     *
     * @param string $url
     * @param string $requestMethod
     * @return array
     */
    public function match($url, $requestMethod = 'GET')
    {
        $requestMethod = strtoupper($requestMethod);
        // ルートツリーの参照
        if (!isset($this->map[$requestMethod])) {
            return $this->response($url, null);
        }
        $refNode = &$this->map[$requestMethod];


        if (empty($url) || $url == '/') {
            $parts = ['/'];
        } else {
            $parts = explode("/", $url);
        }

        foreach ($parts as $idx => $part) {
            if (empty($part)) {
                continue;
            }

            // URLパーツがルーティング木にあれば
            if (isset($refNode[$part])) {
                // 参照ノードを一つ進める
                $refNode = &$refNode[$part];
            } else {
                // パラメータを調べる
                if (isset($refNode['__params'])) {
                    $result = $this->checkParam($part, $parts, $idx, $refNode['__params']);
                    if ($result === false) {
                        return $this->response($url, null);
                    }
                    $refNode = &$result;
                } else {
                    return $this->response($url, null);
                }
            }
        }

        // 一致したurlの最後のノードに値があれば返す
        if (is_array($refNode)) {
            if (isset($refNode['__node_val'])) {
                return $this->response($url, $refNode['__node_val']);
            }
            return $this->response($url, null);
        } else {
            return $this->response($url, $refNode);
        }
    }

    /**
     * パラメータをチェックしてURLパーツと比較
     *
     * @param array $parts
     * @param integer $idx
     * @param array $__params
     * @return mixed
     */
    protected function checkParam(&$part, &$parts, $idx, &$__params)
    {
        if (isset($parts[$idx+1])) {
            $isLastPart = false;
        } else {
            $isLastPart = true;
        }
        // __paramsの要素と比較
        foreach ($__params as $paramName => $value) {
            if ($isLastPart) {
                // 最後のパーツならば要素は配列ではない
                if (!is_array($value)) {
                    $this->setParams($paramName, $part);
                    return $__params[$paramName];
                }
            } else {
                // まだURLが続くならば要素は配列
                if (is_array($value)) {
                    $this->setParams($paramName, $part);
                    return $__params[$paramName];
                }
            }
        }

        //一致しない場合は、 false
        return false;
    }

    /**
     * ルートパラメータ配列に値をセット
     * :id なら キー値は id となる
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setParams($key, $value)
    {
        $key = substr($key, 1);
        $this->params[$key] = $value;
    }


    /**
     * パラメータ配列を取得
     * 元の配列をクリア
     *
     * @return array
     */
    protected function getParams()
    {
        $params = $this->params;
        $this->params = [];
        return $params;
    }


    /**
     * match()の戻り値
     * [
     *   'callback'  => 登録されている値,
     *   'url' => $url,
     *   'params' => [
     *     'id' => 111, // ルートパラメータならばパラメーター名をキーとした配列を返す
     *   ]
     * ]
     *
     * @param string $url
     * @param mixed $callback
     * @return array
     */
    protected function response($url, $callback)
    {
        return [
            'callback'  => $callback,
            'url' => $url,
            'params' => $this->getParams()
        ];
    }

    public function setRoutes($data)
    {
        $this->routes += $this->sortRoutes($data);
    }

    // $routesを元に多次元ツリー構造を生成
    public function makeTrees()
    {
        // 短い順では、長いパスを登録しづらいので、パスの長い方から登録する
        foreach (array_reverse($this->routes) as $group) {
            foreach ($group as $route) {
                $this->registerRoute($route);
            }
        }
    }

    protected function registerRoute($route)
    {
        $parts = $route['parts'];
        $leaf = array_pop($parts);

        // ルートツリーの参照
        $refNode = &$this->map[$route['method']];

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            } elseif ($part[0] == ':') {
                // ルートパラメータの場合, __paramsキー配下に登録
                // "__params"=>[
                //     ":name"=>"cli@name",
                //     ":id"=>[
                //         "show"=>"id@show"
                //     ]
                if (!isset($refNode['__params'])) {
                    $refNode['__params'] = [];
                }
                // 参照ノードを一つ進める
                $refNode = &$refNode['__params'];
            }
            // URLパーツが登録済みならば
            if (isset($refNode[$part])) {
            } else { // URLパーツが未登録
                $refNode[$part] = [];
            }
            // 参照ノードを一つ進める
            $refNode = &$refNode[$part];
        }

        // 末端パーツに対応するノードに葉の値をセット
        if (!isset($refNode[$leaf]) || !is_array($refNode[$leaf])) {
            if ($leaf[0] == ':') {
                // ルートパラメータの場合, __paramsキー配下に登録
                // "__params"=>[
                //     ":name"=>"cli@name",
                //     ":id"=>[
                //         "show"=>"id@show"
                //     ]
                if (!isset($refNode['__params'])) {
                    $refNode['__params'] = [];
                }
                // 参照ノードを一つ進める
                $refNode = &$refNode['__params'];
            }
            $refNode[$leaf] = $route['callback'];
        } else {
            $refNode[$leaf]['__node_val'] = $route['callback'];
        }
    }

    protected function makeRoute($method, $path, $callback)
    {
        if ($path == '/') {
            $parts = ['/'];
        } else {
            $parts = explode("/", $path);
        }
        return [count($parts) ,["method"=> $method,"parts"=>$parts,"callback"=>$callback]];
    }

    // ルート配列をパスのパーツの数ごとにグループ分け
    protected function sortRoutes($routes)
    {
        $sorted = [];
        foreach ($routes as  $data) {
            list($method, $path, $callback) = $data;
            if ($path == '/') {
                $sorted[1][] = ["method"=> $method,"parts"=>['/'],"callback"=>$callback];
                continue;
            }

            $parts = explode("/", $path);
            $sorted[count($parts)][] = ["method"=> $method,"parts"=>$parts,"callback"=>$callback];
        }

        return $sorted;
    }
}
