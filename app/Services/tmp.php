<?php

$path = '/Users/kaiyuwei/Downloads/shorterJson.json';

if ($raw = file_get_contents($path, true)) {
    // decode the raw data in to an array
    $dataArr = json_decode($raw, true);
    
    // dispatch the job to the taskqueue
    // JsonDataImportJob::dispatch($dataArr);

    // loop over the array. Each item of the array is one row to insert
    foreach ($dataArr as $row) {
        // store the credit card information as json string
        $row['credit_card'] = json_encode($row['credit_card']);
        
        // preprocess the boolean value. 
        $row['checked'] = $row['checked'] ? 1 : 0;
        
        // preprocess any null values
        $row = array_map(fn($value) => is_null($value) ? 'NULL' : $value, $row);

        // the keys and values as a string
        $keys = implode(", ", array_keys($row));
        $values  = implode(", ", array_values($row));

        var_dump($roW);
        var_dump(array_values($row));
        var_dump($values);
    }
}
