<?php
namespace App\Services;

use App\Jobs\JsonDataImportJob;
use Illuminate\Support\Facades\DB;
use JsonMachine\Items;

class DataImporter {
    /**
     * batch size
     */
    const BATCH_SIZE = 5;
    /**
     * Read data from a .json file and populate these data to a database.
     * 
     * @param string path to the file
     * @return bool true if the import is successful, false otherwise
     */
    public static function importJSON (string $path) {
        // the count of processed numbers
        $batchCount = 0;

        // the file datastream
        $source = Items::fromFile($path);

        // for test
        $count = 0;

        try {
            // read the fille chunk by chunk
            foreach ($source as $chunk) {
                // for test purpose
                if ($count < 1000) {
                    // the $chunk is an stdClass instance. convert it to an associated array
                    $chunkArray = get_object_vars($chunk);

                    // add the chunck into the batch
                    $batch[] = $chunkArray;

                    // if the number of chunks reach the class batch size, dispatch a job.
                    if (count($batch) == self::BATCH_SIZE) {
                        // dispatch the job to the taskqueue
                        JsonDataImportJob::dispatch($batch);

                        // reset the batch array
                        $batch = [];
                    }

                    $count++;
                }
                else break;
            }

            // indicating the success
            return true;
        }
        catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }        
    }
}