<?php

class URLGenerator
{
    protected $params = [
        ':id',':name',':role',':tag',':image',':key',':class',':category',':rank',
    ];
    protected $parts = [
        'users','books','products','tasks','images','profiles','ranks','orders',
        'payments','news','articles','areas','categories','classes',
    ];


    protected function makeWord($isParam = false)
    {
        if ($isParam) {
            return $this->params[random_int(0, count($this->params) -1)];
        }

        return $this->parts[random_int(0, count($this->parts) -1)];
    }


    protected function createUrl()
    {
        $url = '/';
        $partNum = random_int(2, 8);

        for ($i=0; $i < $partNum; $i++) {
            if ($i > 2 && random_int(0, 4) == 0) {
                $words[] = $this->makeWord(true);
            } else {
                $words[] = $this->makeWord();
            }
        }
        return $url . implode('/', $words);
    }

    public function generateUrls($nums = 100)
    {
        $this->urls = [];
    
        for ($i=0; $i < $nums; $i++) {
            $this->urls[] = $this->createUrl();
        }

        $this->createSamples();
    }

    protected function createSamples()
    {
        foreach ($this->urls as $key => $url) {
            // /e9l/dy76/8984/g8g/:id/:name
    
            $sample = str_replace(':id', 123, $url);
            $sample = str_replace(':name', 'hoge', $sample);
            $sample = str_replace(':role', 'admin', $sample);
            $sample = str_replace(':image', 'selfee', $sample);
            $sample = str_replace(':rank', 'toptier', $sample);
            $sample = str_replace(':category', 'clothes', $sample);
            $sample = str_replace(':class', 'first', $sample);
            $sample = str_replace(':tag', 'work', $sample);
            $sample = str_replace(':key', 'lemon', $sample);

            if ($key % 10 == 0) {
                $sample = str_replace('/w', '/ww', $sample);
            }
            $this->samples[] = $sample;
        }
    }


    public function saveToFile($path = './')
    {
        $contents = implode("\n", $this->urls);

        file_put_contents($path . 'urls.csv', $contents);

        $contents = implode("\n", $this->samples);

        file_put_contents($path . 'samples.csv', $contents);
    }
}
