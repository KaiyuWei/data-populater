<?php

$filepath = "/Users/kaiyuwei/Downloads/csv_data.csv";

$source = fopen($filepath,"r");

$lineCount = 0;

while (($data = fgetcsv($source)) !== FALSE && $lineCount < 10) {
    $keys = ['name',
            'address',  
            'checked',  
            'description',   
            'interest', 
            'date_of_birth', 
            'email',    
            'account',  
            'credit_card',];
    var_dump(array_combine($keys, $data));
    $lineCount++;
}