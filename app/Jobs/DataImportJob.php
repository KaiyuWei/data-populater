<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\DataImporter;
use App\Services\DataImporter\ProgressTracker;
use App\Jobs\DataImportJobFilter\DataImportJobFilter;

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
     * the array filtering rows in the batch
     * @var array<bool>
     */
    private $dataFilterArray;

    /**
     * @var array the config for datafilter
     */
    private $filterConfig;

    /**
     * Create a new job instance.
     * @param array array of key-value pairs
     * @param array array of bytes of chunks this job will process
     * @param int the id of the file this job works for
     * @param int the point in the file where this job starts processing
     * @param array data filter configuarition
     */
    public function __construct(array $dataArray, array $chunkBytes, int $fileId, int $start, array $filterConfig)
    {
        $this->dataArray = $dataArray;
        // bool values determining if a row should be written into the database
        $this->dataFilterArray = array_fill(0, count($dataArray), true);
        $this->fileId = $fileId;
        // the progress tracker for this job
        $this->tracker = new ProgressTracker($chunkBytes, $this, $start);
        $this->filterConfig = $filterConfig;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // generate the datafileter for this class
        $this->dataFilterArray = (new DataImportJobFilter())->generateFilterArray($this->dataArray, $this->filterConfig);

        // prepare the tracker
        // $this->tracker->rewind();
        try{
                // loop over the array. Each item of the array is one row to insert
                for ($i = 0; $i < count($this->dataArray); $i++) {
                    $row = $this->dataArray[$i];

                    // ######### for test #########
                    // if ($row['name'] == "Deonte Harvey") throw new \Exception('Job terminated!');

                    // insert one row to the database if the filter allows
                    if ($this->dataFilterArray[$i]) {
                        // the keys as a string
                        $keys = implode(", ", array_keys($row));
                        DB::insert("insert into clients ($keys) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));
                    }

                    // one chunk has been processed, move the pointer to the next chunk.
                    $this->tracker->next();
                }
        } catch (\Exception $e) {
            // the tracker
            $tracker = $this->tracker;

            // create debris for the chunks that has not been successfully added in the database
            while ($tracker->valid()) {
                // apply filter to only write the rows allowed by the filter in debris table
                if ($this->dataFilterArray[$tracker->key()]) {
                    // write this chunk debris into the database
                    DB::insert('insert into chunk_debris (file_id, start_point, chunk_size) values (?, ?, ?)', [$this->fileId, $tracker->bytesAhead(), $tracker->current()]);
                }
                $tracker->next();
            }

            // notify the jobImporter that dispatches this job about the throw
            throw new \Exception("Job terminated accidentally");
        }
    }
}
