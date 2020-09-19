<?php
require './vendor/autoload.php';

$generator = new URLGenerator();

$generator->generateUrls(300);

$generator->saveToFile();
