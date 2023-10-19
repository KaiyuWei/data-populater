<?php

$filepath = "/Users/kaiyuwei/Downloads/shorter.json";

$hash = hash_file('sha256', $filepath);

var_dump($hash);
var_dump(strlen($hash));