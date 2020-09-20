<?php
/**
 * Router.php
 * リクエストメソッドとURLから対応するController等の値を返す
 * ルーティング用のクラス
 *
 *
 * @author tekku-taro @2020
 */

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

    /**
     * ルーティング木の葉の値を保存した配列
     *
     * @var array
     */
    protected $mapValues = [];

    /**
     * ルートパラメータ配列
     *
     * @var array
     */
    protected $params = [];

    /**
     * ルート登録時に使うtemp配列
     *
     * @var array
     */
    protected $routes = [];


    protected $routesPath;

    /**
     * キャッシュファイルパス
     *
     * @var string
     */
    protected $cachePath;

    /**
     * キャッシュを使用するか
     *
     * @var bool
     */
    protected $useCache;

    /**
     * デフォルトのオプション値
     *
     * @var array
     */
    protected $defaultOptions =[
        'middleware' => ['web']
    ];

    /**
     * groupメソッド実行時に使用
     *
     * @var array
     */
    protected $group = [];


    /**
     * インスタンス生成時にキャッシュを使うか指定
     *
     * @param boolean $useCache
     * @param string $cachePath
     */
    public function __construct($useCache = true, $cachePath = __DIR__ .  '/../routes/cache')
    {
        $this->cachePath = $cachePath . '/cache_routes.cache';
        $this->useCache = $useCache;
    }


    /**
     * routes.phpファイルのルーティング定義を読み込む
     *
     * @param string $routesPath
     * @return void
     */
    public function loadRoutes($routesPath = __DIR__ .  '/../routes/routes.php')
    {
        $this->routesPath = $routesPath;

        if ($this->useCache) {
            $this->checkCache();
        } elseif (!empty($routesPath)) {
            $this->registerFromRoutesFile();
        }
    }

    /**
     * ルーティングファイルからルートを登録
     *
     * @return void
     */
    protected function registerFromRoutesFile()
    {
        $routing = function ($router) {
            include $this->routesPath;
        };

        $routing($this);
        $this->registerRoutes();
    }

    /**
     * キャッシュをチェックし、ルーティングファイルよりも更新日が
     * 新しければ読み込む、古ければルーティングファイルから登録
     *
     * @return void
     */
    protected function checkCache()
    {
        if (file_exists($this->routesPath)) {
            $lastModified = filemtime($this->routesPath);
        } else {
            if (file_exists($this->cachePath)) {
                $this->loadCache();
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

    /**
     * ルーティング木配列をキャッシュに保存
     *
     * @return void
     */
    public function saveCache()
    {
        $json = json_encode(["map"=>$this->map,"mapValues"=>$this->mapValues]);

        file_put_contents($this->cachePath, $json);
    }

    /**
     * ルーティング木配列をキャッシュからロード
     *
     * @return void
     */
    public function loadCache()
    {
        $data = file_get_contents($this->cachePath);
        if (!empty($data)) {
            $array = json_decode($data, true);
            $this->map = $array["map"];
            $this->mapValues = $array["mapValues"];
        }
    }

    /**
     * ルーティング木の構造を分かりやすく表示
     *
     * @return void
     */
    public function showTrees()
    {
        $map = $this->map;
        array_walk_recursive($map, function (&$item) {
            if (!is_array($item)) {
                $mapValue = $this->mapValues[$item];
                $item = $mapValue['callback'] . '[options:' . implode(';', $this->flattenArray($mapValue['options'])) . ']';
            }
        });
        print_r($map);
    }

    /**
     * ２次元配列を１次元配列に変換
     *
     * @param array $array
     * @return array
     */
    protected function flattenArray($array)
    {
        $flattened = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened[] = $key . "=" . implode(",", $value);
            } else {
                $flattened[] = $key . "=" . $value;
            }
        }
        return $flattened;
    }

    /**
     * ルーティング木配列を空にする
     *
     * @return void
     */
    public function clearTrees()
    {
        $this->map = [];
        $this->mapValues = [];
    }

    /**
     * デフォルトオプションと与えられたオプションをマージ
     *
     * @param array $options
     * @return array
     */
    protected function mergeOptions($options = [])
    {
        if (!empty($options)) {
            return array_merge($this->defaultOptions, $options);
        } else {
            return $this->defaultOptions;
        }
    }

    /**
     * GETリクエストのURLのルートを登録
     *
     * @param string $url
     * @param mixed $callback
     * @param array $options
     * @return void
     */
    public function get($url, $callback, $options = [])
    {
        list($key, $value) =  $this->makeRoute('GET', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }

    /**
     * POSTリクエストのURLのルートを登録
     *
     * @param string $url
     * @param mixed $callback
     * @param array $options
     * @return void
     */
    public function post($url, $callback, $options = [])
    {
        list($key, $value) =  $this->makeRoute('POST', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }

    /**
     * PUTリクエストのURLのルートを登録
     *
     * @param string $url
     * @param mixed $callback
     * @param array $options
     * @return void
     */
    public function put($url, $callback, $options = [])
    {
        list($key, $value) =  $this->makeRoute('PUT', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }
    
    /**
     * DELETEリクエストのURLのルートを登録
     *
     * @param string $url
     * @param mixed $callback
     * @param array $options
     * @return void
     */
    public function delete($url, $callback, $options = [])
    {
        list($key, $value) =  $this->makeRoute('DELETE', $url, $callback, $options);
        $this->routes[$key][] = $value;
    }

    /**
     * CRUDの７つのメソッドへのルートを一括登録
     *
     * @param string $url
     * @param mixed $callback
     * @param array $options
     * @return void
     */
    public function controller($url, $controller, $options = [])
    {
        $param = explode('/', ltrim($url, '/'))[0] ;
        if (empty($param)) {
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


    /**
     * groupメソッドで共通処理を適用
     *
     * @param array $groupParam
     * @param callback $callback
     * @return void
     */
    public function group($groupParam, $callback)
    {
        $prefix = '';
        $options = [];
        foreach ($groupParam as $item => $itemValue) {
            switch ($item) {
                case 'prefix':
                    $prefix = $itemValue;
                    break;
                case 'middleware':
                    if (is_array($itemValue)) {
                        $options['middleware'] = $itemValue;
                    } else {
                        $options['middleware'] = [$itemValue];
                    }
                    break;
            }
        }
        
        $this->pushGroupParam($prefix, $options);
        
        $callback($this);
        
        $this->popGroupParam($prefix, $options);
    }


    /**
     * 最後に追加したgroupのパラメータデータを$groupから削除
     *
     * @param string $prefix
     * @param array $options
     * @return void
     */
    protected function popGroupParam($prefix, $options)
    {
        if (!empty($prefix)) {
            array_pop($this->group['prefix']);
        }
        if (!empty($options)) {
            array_pop($this->group['options']);
        }
    }

    /**
     * groupのパラメータデータを$groupに追加
     *
     * @param string $prefix
     * @param array $options
     * @return void
     */
    protected function pushGroupParam($prefix, $options)
    {
        if (!empty($prefix)) {
            $this->group['prefix'][] = $prefix;
        }
        if (!empty($options)) {
            $this->group['options'][] = $options;
        }
    }

    /**
     * $routesのルート情報をルート木に登録
     *
     * @param array $routes
     * @return void
     */
    public function registerRoutes($routes = null)
    {
        if ($routes) {
            $this->setRoutes($routes);
        }

        //urlの長い順に並び替え
        krsort($this->routes);
        $this->makeTrees($this->routes);
        $this->routes = [];

        // useCache == true ならキャッシュに保存
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
                // 最後のパーツで要素は配列ではない
                if (!is_array($value)) {
                    $this->setParams($paramName, $part);
                    return $__params[$paramName];
                }
                // ノードの値(__node_val)が設定されていれば
                if (isset($__params[$paramName]['__node_val'])) {
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
     *   'options' => []
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

    /**
     * $routes配列にソートした$dataを追加
     *
     * @param array $data
     * @return void
     */
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

    /**
     * ルートをルート木に登録
     *
     * @param array $route
     * @return void
     */
    protected function registerRoute($route)
    {
        $optionParam = false;
        $parts = $route['parts'];
        $leaf = array_pop($parts);

        //?オプションのチェック
        if (substr($leaf, -1) == '?') {
            $optionParam = true;
            $lastParam = rtrim($leaf, '?');
            $leaf = array_pop($parts);
        }

        // ルートツリーの参照
        $refNode = &$this->map[$route['method']];

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            } elseif ($part[0] == ':') {
                $last = substr($part, -1);
                if ($last == '?') {
                    throw new ErrorException(implode('/', $route['parts']) . '  ルートの途中にオプションパラメータを付けることはできません！');
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
        if ($optionParam) {
            // URLパーツが登録済みならば
            if (isset($refNode[$leaf])) {
            } else { // URLパーツが未登録
                $refNode[$leaf] = [];
            }
            // 参照ノードを一つ進める
            $lastRefNode = &$refNode[$leaf];
            
            // 末端パーツに対応するノードに葉の値をセット
            $this->setLeafValToNode($lastRefNode, $lastParam, $route);
            // オプションなしの場合も登録
            $this->setLeafValToNode($refNode, $leaf, $route);
        } else {
            // 末端パーツに対応するノードに葉の値をセット
            $this->setLeafValToNode($refNode, $leaf, $route);
        }
    }

    /**
     * ノードに値を保存
     *
     * @param array $refNode
     * @param mixed $leaf
     * @param array $route
     * @return void
     */
    protected function setLeafValToNode(&$refNode, $leaf, $route)
    {
        if (!isset($refNode[$leaf]) || !is_array($refNode[$leaf])) {
            if ($leaf[0] == ':') {
                if (!isset($refNode['__params'])) {
                    $refNode['__params'] = [];
                }
                // 参照ノードを一つ進める
                $refNode = &$refNode['__params'];
            }
            if (isset($refNode[$leaf]) && is_array($refNode[$leaf])) {
                $this->setNodeVal($refNode[$leaf]['__node_val'], $route);
            } else {
                $this->setNodeVal($refNode[$leaf], $route);
            }
        } else {
            $this->setNodeVal($refNode[$leaf]['__node_val'], $route);
        }
    }

    /**
     * ノードへの参照にidをセットし、そのidをキーとしたルート情報を$mapValuesに保存
     *
     * @param array $refNode
     * @param array $route
     * @return void
     */
    protected function setNodeVal(&$refNode, $route)
    {
        $nodeId = uniqid();
        $this->mapValues[$nodeId] = ["callback"=>$route['callback'],"options"=>$route['options']];
        $refNode = $nodeId;
    }

    /**
     * $routesに追加するために、引数からの情報をURLの長さと共に
     * 決まった配列の書式で返す
     *
     * @param string $method
     * @param string $path
     * @param mixed $callback
     * @param array $options
     * @return array
     */
    protected function makeRoute($method, $path, $callback, $options)
    {
        if (!empty($this->group)) {
            $options = $this->addGroupOptions($options, $this->group['options']);
            $path = $this->joinPath($this->group['prefix'], $path);
        }
        $options = $this->mergeOptions($options);
        if ($path == '/') {
            $parts = ['/'];
        } else {
            $parts = explode("/", $path);
        }
        return [count($parts) ,["method"=> $method,"parts"=>$parts,"callback"=>$callback,"options"=>$options]];
    }

    /**
     * $groupのoptions配列を$optionsに追加
     *
     * @param array $options
     * @param array $groupOptionsArray
     * @return array
     */
    protected function addGroupOptions($options, $groupOptionsArray)
    {
        foreach ($groupOptionsArray as $key => $groupOptions) {
            $options += $groupOptions;
        }

        return $options;
    }

    /**
     * 引数のURLを結合する
     *
     * @param array $prefixes
     * @param string $path
     * @return void
     */
    protected function joinPath($prefixes, $path)
    {
        foreach ($prefixes as $key => $value) {
            if ($key == 0) {
                $trimmed[] = rtrim($value, '/');
                continue;
            }
            $trimmed[] = trim($value, '/');
        }
        $trimmed[] = trim($path);
        return join('/', $trimmed);
    }

    /**
     * $routesのデータをパスのパーツの数ごとにグループ分けして返す
     *
     * @param array $routes
     * @return array
     */
    protected function sortRoutes($routes)
    {
        $sorted = [];
        foreach ($routes as  $data) {
            list($method, $path, $callback) = $data;
            if (isset($data[3])) {
                $options = $this->mergeOptions($data[3]) ;
            } else {
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
