<?php

class URLGenerator
{
    protected $letters = 'abcdefghijklmnopqrstuvwxyz-123456789';
    protected $letLen;
    protected $params = [
        ':id',':name',':role'
    ];


    protected function makeWord($isParam = false)
    {
        if ($isParam) {
            return $this->params[random_int(0, count($this->params) -1)];
        }

        $wdLen = random_int(3, 6);
        $word = '';
        for ($i=0; $i < $wdLen; $i++) {
            $idx = random_int(0, $this->letLen-1);
            $letter = $this->letters[$idx];
            $word .= $letter;
        }

        return $word;
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
        $this->letLen =strlen($this->letters);
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
            $sample = str_replace('/x', '/xx', $sample);
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
