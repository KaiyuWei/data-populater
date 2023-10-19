<?php
/**
 * This is for import data that have failed in being imprted by JsonDataIMportJob.
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RemoveJsonDebrisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The path to the file 
     * @var string
     */
    private $file;

    /**
     * The file id stored in the database
     * @var int
     */
    private $fileId;

    /**
     * Create a new job instance.
     * @param string path to the file
     * @param int the file ID stored in the database 'external_files' table
     */
    public function __construct($file, $fileId)
    {
        // the file that the debris from
        $this->file = $file;
        $this->fileId = $fileId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // the file handler, open for read only
            $source = fopen($this->file,"r");

            // collect all the debris data
            $debrisBag = DB::table('chunk_debris')->select('id', 'start_point', 'chunk_size')->where('file_id', "=", $this->fileId)->get();

            foreach ($debrisBag as $debris)
            {
                // move the pointer to the start of the chunk
                fseek($source, $debris->start_point + 1);

                // read data from the file. 
                $raw = fread($source, $debris->chunk_size - 1);

                // convert the raw data to an associated array, and modify the data format
                $row = $this->preprocess(get_object_vars(json_decode($raw)));
                $keys = implode(", ", array_keys($row));

                // insert one row to the database
                DB::insert("insert into clients ($keys) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", array_values($row));

                // insertion success, remove this debris from the database
                DB::table('chunk_debris')->where('id', '=', $debris->id)->delete();
            }

            fclose($source);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            // close the file stream
            fclose($source);
        }
    }

    /**
     * preprocess data to make the values follow SQL data format
     * @param array array of key-value pairs
     * @return array the processed data
     */
    private function preprocess(array $row) {
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
        return $row;
    }
}
