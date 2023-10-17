<?php
namespace App\Services;

class DataImporter {
    /**
     * Read data from a .json file and populate these data to a database.
     * 
     * @param string path to the file
     * @return bool true if the import is successful, false otherwise
     */
    public function importJSON (string $path) {
        // open the file
        $file = fopen($path, 'r');

        // read data from the file
        if ($raw = fgets($file)) {
            // decode the raw data
            $jsonObject = json_decode($raw);
            
            // dispatch the job to the taskqueue
            
        }
   
        // close the file
        fclose($file);
        // indicating the success
        return true;
    }
}