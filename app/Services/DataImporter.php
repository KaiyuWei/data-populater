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
    public static function importJSON (string $path) {

        // @todo can we find a way to read the file chunk by chunk?
        // read data from the file
        if ($raw = file_get_contents($path, true)) {
            // decode the raw data in to an array
            $dataArray = json_decode($raw, true);
            
            // dispatch the job to the taskqueue
            JsonDataImportJob::dispatch($dataArray);
        }
   
        // indicating the success
        return true;
    }
}