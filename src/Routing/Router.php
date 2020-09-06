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

    public function showTree()
    {
        print_r($this->map);
    }

    // $routesを元に多次元ツリー構造を生成
    public function makeTree($routes)
    {
        $routes = $this->sortRoutes($routes);

        // 短い順では、長いパスを登録しづらいので、パスの長い方から登録する
        foreach (array_reverse($routes) as $group) {
            foreach ($group as $route) {
                $parts = $route['parts'];
                $leaf = array_pop($parts);
                $callback = $route['callback'];

                // ルートツリーの参照
                $refNode = &$this->map;

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
                    $refNode[$leaf] = $callback;
                } else {
                    $refNode[$leaf]['__node_val'] = $callback;
                }
            }
        }
    }

    // ルート配列をパスのパーツの数ごとにグループ分け
    protected function sortRoutes($routes)
    {
        $sorted = [];
        foreach ($routes as $path => $callback) {
            $parts = explode("/", $path);
            $sorted[count($parts)][] = ["parts"=>$parts,"callback"=>$callback];
        }

        return $sorted;
    }
}
