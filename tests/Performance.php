<?php

class Performance
{
    protected $urls;
    protected $samples;
    protected $tests;
    protected $routes;
    protected $results;
    protected $treeCreatingTimes;
    protected $routingTimes;
    protected $numOfRuns = 5;
    protected $timeStart;
    protected $timeMid;
    protected $timeEnd;

    public function __construct()
    {
        $this->loadTestData();
    }

    protected function loadTestData()
    {
        $this->urls = explode("\n", file_get_contents(__DIR__ . '/data/urls.csv')) ;
        $this->samples = explode("\n", file_get_contents(__DIR__ . '/data/samples.csv')) ;
        $this->pickTestSamples();
        // shuffle($this->samples);

        $this->createRoutes();
    }

    protected function pickTestSamples()
    {
        for ($i=0; $i < $this->numOfRuns; $i++) {
            $this->tests[] = $this->samples[array_rand($this->samples)];
        }
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
        for ($i=0; $i < $this->numOfRuns; $i++) {
            $this->execPerfomance($i, false);
        }

        $this->showResults();
    }

    public function testPerformanceWithCache()
    {
        for ($i=0; $i < $this->numOfRuns; $i++) {
            $this->execPerfomance($i, true);
        }

        $this->showResults(true);
    }
    protected function execPerfomance($idx, $useCache)
    {
        $router = new Taro\Routing\Router($useCache);
        $this->timeStart();
        if ($useCache) {
            $router->loadCache();
        } else {
            $router->registerRoutes($this->routes);
        }

        
        $this->timeMid();

        // foreach ($this->samples as $key => $sample) {
        //     $this->results[] = ($router->match($sample));
        // }
        $this->results[] = ($router->match($this->tests[$idx]));

        $this->timeEnd();

        if (!$useCache) {
            $router->saveCache();
            $this->saveResults();
        }

        $this->treeCreatingTimes[] = $this->timeMid - $this->timeStart;
        $this->routingTimes[] = $this->timeEnd - $this->timeMid;
    }



    protected function showResults($useCache = false)
    {
        $msg = '';
        if ($useCache) {
            $msg = 'キャッシュ使用';
        }
        print $this->numOfRuns . "回の平均値：" . PHP_EOL;
        print $msg.'ルーティング作成時間：' . $this->getAverage($this->treeCreatingTimes) . ' 秒' . PHP_EOL;
        print $msg.'実行時間：' . $this->getAverage($this->routingTimes) . ' 秒' . PHP_EOL;
    }

    protected function getAverage($timeRecords)
    {
        return array_sum($timeRecords) / count($timeRecords);
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
