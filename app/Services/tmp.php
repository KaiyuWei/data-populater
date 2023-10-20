<?php

$filepath = "/Users/kaiyuwei/Downloads/shorter.json";

$source = fopen($filepath,"r");

fseek($source, 4272);

$raw = fread($source, 944);

var_dump($raw);