<?php

$filepath = "/Users/kaiyuwei/Downloads/shorter.json";

$source = fopen($filepath,"r");

fseek($source, 4271);

$raw = fread($source, 2000);

var_dump($raw);