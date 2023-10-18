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
     * @param array array of json objects
     */
    public function __construct(array $dataArray)
    {
        $this->dataArray = $dataArray;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // loop over the array. Each item of the array is one row to insert
        foreach ($this->dataArray as $row) {
            // store the credit card information as json string
            $row['credit_card'] = json_encode($row['credit_card']);
            
            // preprocess the boolean value. 
            $row['checked'] = $row['checked'] ? 1 : 0;
            
            // preprocess any null values
            $row = array_map(fn($value) => is_null($value) ? 'NULL' : $value, $row);

            // the keys and values as a string
            $keys = implode(", ", array_keys($row));
            
            // insert one row to the database
            DB::insert("insert into clients ($keys) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));
        }
    }
}
