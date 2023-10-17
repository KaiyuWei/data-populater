<?php
$file = fopen('/Users/kaiyuwei/Downloads/challenge_1610.json', 'r');
$line = fgets($file, true);
fclose($file);

$obj = json_decode($line);
var_dump(gettype($obj));
