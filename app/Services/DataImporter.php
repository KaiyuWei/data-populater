<?php
namespace App\Services;

use App\Jobs\JsonDataImportJob;
use Illuminate\Support\Facades\DB;

class DataImporter {
    /**
     * Read data from a .json file and populate these data to a database.
     * 
     * @param string path to the file
     * @return bool true if the import is successful, false otherwise
     */
    public function importJSON (string $path) {

        // @todo can we find a way to read the file chunk by chunk?
        // read data from the file
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
                
                // insert one row to the database
                DB::insert("insert into clients ({$keys}) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));
            }
        }
   
        // indicating the success
        return true;
    }
}