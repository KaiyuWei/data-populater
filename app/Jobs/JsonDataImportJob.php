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

class JsonDataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 0;

    /**
     * The number of seconds the job can run before timing out.
     * 
     * @var int
     */
    public $timeout = 1;

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
     * @param int the point in the file where this job starts processing
     */
    public function __construct(array $dataArray, array $chunkBytes, int $start)
    {
        // preprocess the data before store it in the class
        $this->dataArray = $this->preprocess($dataArray);

        // the progress tracker for this job
        $this->tracker = new ProgressTracker($chunkBytes, $this, $start);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // prepare the tracker
        $this->tracker->rewind();

        // loop over the array. Each item of the array is one row to insert
        foreach ($this->dataArray as $row) {

            // the keys and values as a string
            $keys = implode(", ", array_keys($row));
            
            // insert one row to the database
            DB::insert("insert into clients ($keys) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));

            // one chunk has been processed, move the pointer to the next chunk.
            $this->tracker->next();
        }
    }

    /**
     * preprocess data to make the values follow SQL data format
     * @param array array of key-value pairs
     * @return array the processed data
     */
    private function preprocess(array $data) {
        $result = [];
        // loop over the array. Each item of the array is one row to insert
        foreach ($data as $row) {
            // preprocess the boolean value. 
            $row['checked'] = $row['checked'] ? 1 : 0;

            // preprocess the datatime values
            // the format 'dd/mm/yyyy' cannot be recognised by SQL datetime datatype
            if (!is_null($dateTime = $row['date_of_birth']) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateTime)) {
                
                // convert it to sql datetime format
                $row['date_of_birth'] = \DateTime::createFromFormat('d/m/Y', $dateTime)->format('Y-m-d H:i:s');
            }

            // preprocess the json values
            if ($card = $row['credit_card'])  $row['credit_card'] = json_encode($card);
            
            // preprocess any null values
            $row = array_map(fn($value) => is_null($value) ? null : $value, $row);

            // append the modified data to the result
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        //
    }
}
