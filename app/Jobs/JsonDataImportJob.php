<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class JsonDataImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    /**
     * the data to be write into the database
     * @var array
     */
    protected $dataArray;

    /**
     * Create a new job instance.
     * @param array array of key-value pairs
     */
    public function __construct(array $dataArray)
    {
        // preprocess the data before store it in the class
        $this->dataArray = $this->preprocess($dataArray);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // loop over the array. Each item of the array is one row to insert
        foreach ($this->dataArray as $row) {

            // the keys and values as a string
            $keys = implode(", ", array_keys($row));
            
            // insert one row to the database
            DB::insert("insert into clients ($keys) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));
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
}
