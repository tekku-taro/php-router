<?php

class Performance
{
    protected $urls;
    protected $samples;
    protected $routes;
    protected $results;

    protected function loadTestData()
    {
        $this->urls = explode("\n", file_get_contents(__DIR__ . '/data/urls.csv')) ;
        $this->samples = explode("\n", file_get_contents(__DIR__ . '/data/samples.csv')) ;
        shuffle($this->samples);

        $this->createRoutes();
    }

    protected function saveResults()
    {
        $content = '';
        foreach ($this->results as $key => $result) {
            $params = '';
            foreach ($result['params'] as $key => $value) {
                $params .= $key . '=' . $value . '  ';
            }
            $content .= 'URL:' . $result['url'] . ' CALLBACK:' .   $result['callback'] . ' PARAMS:' . $params . PHP_EOL;
        }
        
        file_put_contents(__DIR__ . '/data/results.csv', $content);
    }

    public function testPerformance()
    {
        $this->loadTestData();
        $this->timeStart();
        $router = new Taro\Routing\Router();
        $router->registerRoutes($this->routes);
        
        $this->timeMid();

        foreach ($this->samples as $key => $sample) {
            $this->results[] = ($router->match($sample));
        }
        $this->timeEnd();

        $this->showResults();
        $this->saveResults();
    }

    public function testPerformanceWithCache()
    {
        $this->timeStart();
        $router = new Taro\Routing\Router();
        $router->loadCache();
        
        $this->timeMid();

        foreach ($this->samples as $key => $sample) {
            $this->results[] = ($router->match($sample));
        }
        $this->timeEnd();

        $this->showResults(true);
    }

    protected function showResults($useCache = false)
    {
        $msg = '';
        if ($useCache) {
            $msg = 'キャッシュ使用';
        }
        print $msg.'ルーティング作成時間：' . ($this->timeMid - $this->timeStart) . ' 秒' . PHP_EOL;
        print $msg.'実行時間：' . ($this->timeEnd - $this->timeMid) . ' 秒' . PHP_EOL;
    }

    protected function createRoutes()
    {
        foreach ($this->urls as $key => $url) {
            $this->routes[] =["GET",$url, 'val' . $key];
        }
    }

    protected function timeStart()
    {
        $this->timeStart = microtime(true);
    }

    protected function timeMid()
    {
        $this->timeMid = microtime(true);
    }

    protected function timeEnd()
    {
        $this->timeEnd = microtime(true);
    }
}
