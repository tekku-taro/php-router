<?php
require './vendor/autoload.php';

$generator = new URLGenerator();

$generator->generateUrls(5000);

$generator->saveToFile();
