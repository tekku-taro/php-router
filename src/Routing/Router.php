<?php
namespace Taro\Routing;

use ErrorException;

class Router
{
    /**
     * ルーティング用の木構造のルート配列
     *
     * @var array
     */
    protected $map = [];
    protected $mapValues = [];

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

    protected $defaultOptions =[
        'middleware' => ['web']
    ];

    public function __construct($useCache = true, $cachePath = __DIR__ .  '/../routes/cache')
    {
        $this->cachePath = $cachePath . '/cache_routes.cache';
        $this->useCache = $useCache;
    }

    public function loadRoutes($routesPath = __DIR__ .  '/../routes/routes.php')
    {
        $this->routesPath = $routesPath;

        if ($this->useCache) {
            $this->checkCache();
        } elseif (!empty($routesPath)) {
            $this->registerFromRoutesFile();
        }
    }

    protected function registerFromRoutesFile()
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
            if (file_exists($this->cachePath)) {
                return $this->loadCache();
            }
            return;
        }

        if (file_exists($this->cachePath)) {
            $lastCached = filemtime($this->cachePath);

            if ($lastCached && ($lastCached > $lastModified)) {
                $this->loadCache();
                return;
            }
        }

        $this->registerFromRoutesFile();
    }

    public function saveCache()
    {
        $json = json_encode(["map"=>$this->map,"mapValues"=>$this->mapValues]);

        file_put_contents($this->cachePath, $json);
    }

    public function loadCache()
    {
        $data = file_get_contents($this->cachePath);
        if (!empty($data)) {
            $array = json_decode($data, true);
            $this->map = $array["map"];
            $this->mapValues = $array["mapValues"];
        }
    }

    public function showTrees()
    {
        $map = $this->map;
        array_walk_recursive($map,function(&$item){
            if(!is_array($item)){
                $mapValue = $this->mapValues[$item];
                $item = $mapValue['callback'];
            }
        });
        print_r($map);
    }


    public function clearTrees()
    {
        $this->map = [];
    }

    protected function mergeOptions($options = [])
    {
        if(!empty($options)){
            return array_merge($this->defaultOptions, $options);

        }else{
            return $this->defaultOptions;
        }
    }

    public function get($url, $callback, $options = [])
    {
        $options = $this->mergeOptions($options);
        list($key, $value) =  $this->makeRoute('GET', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }
    
    public function post($url, $callback, $options = [])
    {
        $options = $this->mergeOptions($options);        
        list($key, $value) =  $this->makeRoute('POST', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }
    
    public function put($url, $callback, $options = [])
    {
        $options = $this->mergeOptions($options);        
        list($key, $value) =  $this->makeRoute('PUT', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }
    
    public function controller($url, $controller, $options = [])
    {
        $param = explode('/',ltrim($url,'/'))[0] ;
        if(empty($param)){
            $param = 'id';
        }
        $this->get($url, $controller.'@index', $options);
        $this->get($url.'/:'. $param, $controller.'@show', $options);
        $this->get($url.'/create', $controller.'@create', $options);
        $this->get($url.'/:'. $param . '/edit', $controller.'@edit', $options);

        $this->post($url, $controller.'@store', $options);
        $this->put($url .'/:'. $param, $controller.'@update', $options);
        $this->delete($url .'/:'. $param, $controller.'@delete', $options);
    }
    
    public function delete($url, $callback, $options = [])
    {
        $options = $this->mergeOptions($options);        
        list($key, $value) =  $this->makeRoute('DELETE', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }
    
    public function registerRoutes($routes = null)
    {
        if ($routes) {
            $this->setRoutes($routes);
        }
        // print_r($this->routes);
        krsort($this->routes);
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
                if(isset($__params[$paramName]['__node_val'])){
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
     * ?を削除
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
     * @param mixed $nodeId
     * @return array
     */
    protected function response($url, $nodeId = null)
    {
        return [
            'callback'  => $nodeId ? $this->mapValues[$nodeId]['callback'] : null,
            'url' => $url,
            'params' => $this->getParams(),
            'options'=> $nodeId ? $this->mapValues[$nodeId]['options'] : []
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
        foreach ($this->routes as $group) {
            foreach ($group as $route) {
                $this->registerRoute($route);
            }
        }
    }

    protected function registerRoute($route)
    {
        $optionParam = false;
        $parts = $route['parts'];
        $leaf = array_pop($parts);

        //?オプションのチェック
        if(substr($leaf,-1) == '?'){
            $optionParam = true;
            $lastParam = rtrim($leaf,'?');
            $leaf = array_pop($parts);
        }

        // ルートツリーの参照
        $refNode = &$this->map[$route['method']];

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            } elseif ($part[0] == ':') {
                $last = substr($part, -1);
                if($last == '?'){
                    throw new ErrorException(implode('/',$route['parts']) . '  ルートの途中にオプションパラメータを付けることはできません！');
                }
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


        //最後にオプションパラメーターがある場合
        if($optionParam){
            // URLパーツが登録済みならば
            if (isset($refNode[$leaf])) {
            } else { // URLパーツが未登録
                $refNode[$leaf] = [];
            }
            // 参照ノードを一つ進める
            $lastRefNode = &$refNode[$leaf];     
            
            // 末端パーツに対応するノードに葉の値をセット
            $this->setLeafValToNode($lastRefNode,$lastParam,$route);  
            // オプションなしの場合も登録          
            $this->setLeafValToNode($refNode,$leaf,$route);            
        }else{
            // 末端パーツに対応するノードに葉の値をセット
            $this->setLeafValToNode($refNode,$leaf,$route);
        }
    }

    // 末端パーツに対応するノードに葉の値をセット
    protected function setLeafValToNode(&$refNode,$leaf,$route)
    {
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
            if(isset($refNode[$leaf]) && is_array($refNode[$leaf])){
                // $refNode[$leaf]['__node_val'] = $route['callback'];
                $this->setNodeVal($refNode[$leaf]['__node_val'], $route);
            }else{
                // $refNode[$leaf] = $route['callback'];
                $this->setNodeVal($refNode[$leaf], $route);                
            }
        } else {
            $this->setNodeVal($refNode[$leaf]['__node_val'], $route);
        }        
    }

    protected function setNodeVal(&$refNode, $route)
    {
        $nodeId = uniqid();
        $this->mapValues[$nodeId] = ["callback"=>$route['callback'],"options"=>$route['options']];
        $refNode = $nodeId;
    }

    protected function makeRoute($method, $path, $callback, $options)
    {
        if ($path == '/') {
            $parts = ['/'];
        } else {
            $parts = explode("/", $path);
        }
        return [count($parts) ,["method"=> $method,"parts"=>$parts,"callback"=>$callback,"options"=>$options]];
    }

    // ルート配列をパスのパーツの数ごとにグループ分け
    protected function sortRoutes($routes)
    {
        $sorted = [];
        foreach ($routes as  $data) {
            list($method, $path, $callback) = $data;
            if(isset($data[3])){
                $options = $this->mergeOptions($data[3]) ;
            }else{
                $options = $this->mergeOptions() ;
            }
            if ($path == '/') {
                $sorted[1][] = ["method"=> $method,"parts"=>['/'],"callback"=>$callback,"options"=>$options];
                continue;
            }

            $parts = explode("/", $path);
            $sorted[count($parts)][] = ["method"=> $method,"parts"=>$parts,"callback"=>$callback,"options"=>$options];
        }

        return $sorted;
    }
}
