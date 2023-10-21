<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\DataImporter\ProgressTracker;

class DataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The id of the file this job works for
     * @var int
     */
    private $fileId;

    /**
     * the data to be write into the database
     * @var array
     */
    protected $dataArray;

    /**
     * the job progress tracker for this job
     * @var ProgressTracker
     */
    private $tracker;

    /**
     * Create a new job instance.
     * @param array array of key-value pairs
     * @param array array of bytes of chunks this job will process
     * @param int the id of the file this job works for
     * @param int the point in the file where this job starts processing
     */
    public function __construct(array $dataArray, array $chunkBytes, int $fileId, int $start)
    {
        // preprocess the data before store it in the class
        $this->dataArray = $dataArray;

        // initialize the file id
        $this->fileId = $fileId;

        // the progress tracker for this job
        $this->tracker = new ProgressTracker($chunkBytes, $this, $start);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // prepare the tracker
        // $this->tracker->rewind();
        try{
                // loop over the array. Each item of the array is one row to insert
                foreach ($this->dataArray as $row) {
                    
                    // the keys as a string
                    $keys = implode(", ", array_keys($row));

        // ######### for test #########
        // if ($row['name'] == "Ms. Una Lynch MD") throw new \Exception('Job terminated!');

                    // insert one row to the database
                    DB::insert("insert into clients ($keys) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));
                
                    // one chunk has been processed, move the pointer to the next chunk.
                    $this->tracker->next();
                }
        } catch (\Exception $e) {
            // the tracker
            $tracker = $this->tracker;

            // create debris for the chunks that has not been successfully added in the database
            while ($tracker->valid()) {

                // write this chunk debris into the database
                DB::insert('insert into chunk_debris (file_id, start_point, chunk_size) values (?, ?, ?)', [$this->fileId, $tracker->bytesAhead(), $tracker->current()]);
                $tracker->next();
            }

            // notify the jobImporter that dispatches this job about the throw
            throw new \Exception("Job terminated accidentally");
        }
    }
}
