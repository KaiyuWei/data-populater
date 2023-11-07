<?php
namespace App\Services;

use App\Jobs\DataImportJob;
use App\Jobs\CsvDataImportJob;
use App\Services\DataImporter\ChunkGenerator;
use App\Jobs\RemoveFileDebrisJob;
use Illuminate\Support\Facades\DB;
use JsonMachine\Items;

class DataImporter {
    /**
     * batch size
     */
    const BATCH_SIZE = 5;

    /**
     * Read data from a file and populate these data to a database.
     * 
     * @param string path to the file
     * @param string the file format type, 'json', 'xml' or 'csv'
     * @param array the config for filters including filter name and arguments. e.g. ['age' => [30, 200, true]]
     * @return bool true if the import is successful, false otherwise
     */
    public static function importFromFile (string $path, $fileType, $filterConfig = []) {
        // hash value of the file
        $filehash = hash_file('sha256', $path);

        // check if it was processed and failed before
        $notNewFile = self::fileFailedBefore($filehash);

        // get the self-incrementing file id in the database
        $fileId = 0;

        // the byte after which the DataImportJob should start
        $startFrom = -1;

        try {
            // if not a new file, we need to process the debris that are not populated to the database
            if ($notNewFile) {

                // get the self-incrementing file id in the database
                $fileId = (self::fileId($filehash))[0]->id;

                // get the point after which the JsonDataImprtJob should start from
                $startFrom = self::getStartPoint($fileId);

                // dispatch a job to handle the debris left before
                RemoveFileDebrisJob::dispatch($path, $fileId);
            }
            else {
                // write the file in the database if it is new
                DB::table('external_files')->insert(['filehash' => $filehash]);

                // get the self-incrementing file id in the database
                $fileId = (self::fileId($filehash))[0]->id;
            }

            // the file datastream
            $source = new ChunkGenerator($path, $fileType);

            // the batch array
            $batch =[];

            // the bytes of each chunk
            $chunkBytes = [];

            // start processing from 0 bytes
            $current = 0;
            
            // initialize the pointer for the position from last iteration
            $batchStart = 0;

            // read the fille chunk by chunk
            foreach ($source->chunks() as $chunk) {
                // current position of the bytes pointer
                $current = $source->getPosition();

                // skip the processed bytes and debris
                if ($current <= $startFrom) continue;

                // add the size of the current chunk
                $chunkBytes[] = $source->getPosition() - $batchStart - array_sum($chunkBytes);

                // the $chunk is an stdClass instance. convert it to an associated array
                $chunkArray = get_object_vars($chunk);

                // add the chunck into the batch
                $batch[] = $chunkArray;

                // if the number of chunks reach the class batch size, dispatch a job.
                if (count($batch) == self::BATCH_SIZE) {
                    // dispatch the job to the taskqueue
                    DataImportJob::dispatch($batch, $chunkBytes, $fileId, $batchStart, $filterConfig);

                    // the end point of the current batch, which is where the next batch starts.
                    $batchStart = $current;

                    // rewind the chunkBytes array for the next batch
                    $chunkBytes = [];
                    
                    // rewind the batch array
                    $batch = [];
                }
            }

            // for now we may still have chunks in the batch that are less then the batch size
            if(!empty($batch)) DataImportJob::dispatch($batch, $chunkBytes, $fileId, $batchStart, $filterConfig);

            // remove the file from the external_fiiles table when no debris of it left
            // if (!self::fileDebrisExist($fileId)) DB::delete("delete from external_files where filehash = '{$filehash}'");
            if (!self::fileDebrisExist($fileId)) DB::table('external_files')->where('filehash', '=', $filehash);

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
     * get the bytes after which DataImportJob should start for a file that has left debris before
     * @param int the file id
     */
    public static function getStartPoint($fileId) {
        // query result order by start_point desc, so the last debris the file made ranks the first
        $result = DB::table('chunk_debris')
                    ->select('start_point', 'chunk_size')
                    ->where('file_id', '=', $fileId)
                    ->orderBy('start_point', 'desc')->get();
        
        // the last debris
        $lastDebris = $result->first();

        // the bytes from which the DataImportJob should starts from
        return $lastDebris->start_point + $lastDebris->chunk_size;
    }

    /**
     * look up the file id by the file hash value
     * @param string the hash value of the file
     * @return int|null the value of the id
     */
    public static function fileId(string $hashvalue) {
        // return DB::select("select id from external_files where filehash = '{$hashvalue}'");
        if (is_null($result = DB::table('external_files')->where('filehash', '=', $hashvalue)->first())) return null;
        else return $result->id;
    }

    /**
     * check if there's any debris to be processed for a file
     * @var int file ID
     * @return bool true if there is debris for the file
     */
    public static function fileDebrisExist($fileId)
    {
        // make query and check if the result is null
        $result = DB::table('chunk_debris')->select('id')->where('file_id', '=', $fileId)->get();

        // if something returned from the query, return true. Otherwise return false.
        return !is_null($result->first());
    }
}