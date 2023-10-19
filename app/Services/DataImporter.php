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
        // hash value of the file
        $filehash = hash_file('sha256', $path);

        //@todo: hash the file and check if it was processed before 
        $failedProcess = self::fileFailedBefore($filehash);

        // if not start from the beginning. Otherwise resume the import from where it was terminated

        // write the file in the database
        DB::insert("insert into external_files (filehash) values ('{$filehash}')");

        // get the self-incrementing file id in the database
        $fileId = (self::fileId($filehash))[0]->id;

        // the count of processed numbers
        $batchCount = 0;

        // the file datastream
        $source = Items::fromFile($path, ['debug' =>true]);

        try {
            // the batch array
            $batch =[];

            // the bytes of each chunk
            $chunkBytes = [];

            // start processing from 0 bytes
            $current = 0;
            // initialize the pointer for the position from last iteration
            $batchStart = 0;

            // read the fille chunk by chunk
            foreach ($source as $chunk) {
                // current position of the bytes pointer
                $current = $source->getPosition();

                // add the size of the current chunk
                $chunkBytes[] = $source->getPosition() - $batchStart - array_sum($chunkBytes);

                // the $chunk is an stdClass instance. convert it to an associated array
                $chunkArray = get_object_vars($chunk);

                // add the chunck into the batch
                $batch[] = $chunkArray;

                // if the number of chunks reach the class batch size, dispatch a job.
                if (count($batch) == self::BATCH_SIZE) {
                    // dispatch the job to the taskqueue
                    JsonDataImportJob::dispatch($batch, $chunkBytes, $fileId, $batchStart);

                    // the end point of the current batch, which is where the next batch starts.
                    $batchStart = $current;

                    // rewind the chunkBytes array for the next batch
                    $chunkBytes = [];
                    
                    // rewind the batch array
                    $batch = [];
                }
            }

            // for now we may still have chunks in the batch that are less then the batch size
            if(!empty($batch)) JsonDataImportJob::dispatch($batch, $chunkBytes, $fileId, $batchStart);

            //remove the file from the external_fiiles table when it is successfully imported
            DB::delete("delete from external_files where filehash = '{$filehash}'");

            // indicating the success
            return true;
        }
        catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }        
    }

    /**
     * look up the hash value of a file in the external_files database to check if a same file
     * failed in importing before.
     * @param string the hash value of the file to be found
     * @return bool true if the same file was processed and failed before, so exists in the database
     */
    public static function fileFailedBefore($filehash) {
        // look for a file by its hash value
        $result = DB::table('external_files')->select('id')->where('filehash', '=', $filehash)->get();

        // if the result is null, the file has never been processed and failed before
        if (is_null($result->first())) return false;

        return true;
    }

    /**
     * expose the file id by the file hash value
     * @var string the hash value of the file
     */
    public static function fileId($hashvalue) {
        return DB::select("select id from external_files where filehash = '{$hashvalue}'");
    }
}