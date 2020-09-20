<?php
require './vendor/autoload.php';
// パフォーマンステスト用URLの生成

$generator = new URLGenerator();

$generator->generateUrls(300);

$generator->saveToFile();
