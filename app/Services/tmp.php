<?php
namespace App\Services;
use JsonMachine\Items;

$path = '/Users/kaiyuwei/Downloads/shorterJson.json';
$clients = Items::fromFile('fruits.json');

$count = 0;

foreach ($clients as $client) {
    if ($count < 2) {
        var_dump($client);
        $count++;
    }
    else break;
}