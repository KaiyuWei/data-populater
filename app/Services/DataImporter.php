<?php
namespace App\Services;

class DataImporter {
    /**
     * Read data from a .json file and populate these data to a database.
     * 
     * @param string path to the file
     */
    public function importJSON (string $path) {
        // open the file
        $file = fopen($path, 'r');

        // read data from the file
        while ($raw = fgets($file)) {
            // decode the raw data
            $jsonObject = json_decode($raw, true);
            if ($jsonObject !== null) {
                // Handle the complete JSON object in $jsonObject
                // Reset $chunk for the next chunk
                $chunk = '';
            }
        }

        fclose($file);
    }
}