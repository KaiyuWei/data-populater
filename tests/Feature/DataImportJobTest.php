<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\DataImporter;
use App\Jobs\RemoveJsonDebrisJob;
use App\Models\ChunkDebris;
use App\Models\ExternalFile;
use App\Models\Client;
use JsonMachine\Items;
use Illuminate\Support\Str;
use App\Services\DataImporter\ChunkGenerator;

class DataImportJobTest extends TestCase
{

    /**
     * test if data is written into the database by DataImportJob
     */
    public function test_job_write_data_to_database(): void
    {
        // the json file we use for testing
        // $filePath = "/Users/kaiyuwei/Downloads/shorter.json";
        $filePath = "/Users/kaiyuwei/Downloads/challenge_1610.json";

        DataImporter::importFromFile($filePath, 'json');

        // check the total number of rows 
        $this->assertEquals(10002, Client::count());
        $this->assertEquals(0, ChunkDebris::count());
        $this->assertEquals(0, ExternalFile::count());

        // data should be there.
        $this->assertDatabaseHas("clients", [
            "name" => "Prof. Simeon Green"
        ]);
        
        $this->assertDatabaseHas("clients", [
            "name" => "Adriel Roob"
        ]);

    }

    /**
     * test if the event can be triggered
     */
    public function test_job_fail_event(): void
    {
        // the json file we use for testing
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importFromFile($filePath, 'json');

        // data should be there.
        $this->assertDatabaseHas("clients", [
            "name" => "Prof. Simeon Green"
        ]);
    }

    public function test_job_tracker(): void
    {
        // the json file we use for testing
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importFromFile($filePath, 'json');

        // data should be there.
        $this->assertDatabaseHas("clients", [
            "name" => "Prof. Simeon Green"
        ]);
    }

    public function test_job_importer_fileId_function ():void 
    {
        $result = DataImporter::fileId('8a13978dc55ad8554547db4bf3be995ce7431da94fd59eed101aca7477bd6795');

        $this->assertEquals(6, $result[0]->id);
    }

    public function test_job_write_chunk_debris(): void
    {
        // the json file we use for testing
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importFromFile($filePath, 'json');
        
        $this->assertDatabaseHas('clients', [
            "name" => "Dandre Bode PhD",
        ]);
        $this->assertDatabaseMissing("clients", [
            "name" => "Kamille Gusikowski",
        ]);
    }

    public function test_remove_json_debris_job (): void
    {
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        $fileId = 26;

        RemoveJsonDebrisJob::dispatch($filePath, $fileId);

        $this->assertDatabaseMissing("chunk_debris", [
            'file_id' => $fileId,
        ]);
    }

    public function test_file_debris_exists(): void{
        $this->assertTrue(DataImporter::fileDebrisExist(31));
        $this->assertFalse(DataImporter::fileDebrisExist(27));
    }

    /**
     * test the 'DataImporter::fileFailedBefore()' function
     */
    public function test_file_failed_before_function(): void{
        $this->assertTrue(DataImporter::fileFailedBefore('8a13978dc55ad8554547db4bf3be995ce7431da94fd59eed101aca7477bd6795'));
        $this->assertFalse(DataImporter::fileFailedBefore('112233ddjjs'));
    }

    public function test_data_importer_get_start_point_function(): void
    {
        DataImporter::getStartPoint(26);
    }

    /**
     * test if the DataImporter can impot a json file that has debris left before
     * 
     * before runnint this test:
     * 1. prepare the external_files debris as:
     * +----+------------------------------------------------------------------+------------+------------+
     * | id | filehash                                                         | created_at | updated_at |
     * +----+------------------------------------------------------------------+------------+------------+
     * | 29 | 8a13978dc55ad8554547db4bf3be995ce7431da94fd59eed101aca7477bd6795 | NULL       | NULL       |
     * +----+------------------------------------------------------------------+------------+------------+
     * 
     * 2. prepare the chunk_debris table as (replace the file_id with the id in the external_files table):
     * +----+---------+-------------+------------+------------+------------+
     * | id | file_id | start_point | chunk_size | created_at | updated_at |
     * +----+---------+-------------+------------+------------+------------+
     * | 35 |      29 |        1981 |        676 | NULL       | NULL       |
     * | 36 |      29 |        2657 |        752 | NULL       | NULL       |
     * | 37 |      29 |        3409 |        861 | NULL       | NULL       |
     * +----+---------+-------------+------------+------------+------------+
     * 
     * 3. delete from client.
     */
    public function test_data_importer_import_json_function_for_an_debris_file(): void
    {
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importFromFile($filePath, 'json');

        // check that the file has been deleted from the database
        $this->assertDatabaseMissing('external_files', [
            'filehash' => '8a13978dc55ad8554547db4bf3be995ce7431da94fd59eed101aca7477bd6795',
        ]);

        // check that the chunk debris has been deleted from the database
        $this->assertEquals(0, ChunkDebris::count());

        // check the total number of rows 
        // there are 6 clients in this file, and the first two has been imported to the database
        $this->assertEquals(4, Client::count());

        // check that all rows have been inserted
        $this->assertDatabaseHas("clients", [
            "name" => "Kamille Gusikowski",
        ]);
        $this->assertDatabaseHas("clients", [
            "name" => "Anthony O'Keefe III",
        ]);
        $this->assertDatabaseHas("clients", [
            "name" => "Camren Koss",
        ]);
        $this->assertDatabaseHas("clients", [
            "name" => "Dr. Testi Tostington",
        ]);
    }

    /**
     * generate the csv file for test
     */
    public function test_create_csv_file() {
        $source = fopen('/Users/kaiyuwei/Downloads/csv_data.csv', 'r+');

        fputcsv($source, ['name', 'address', 'checked', 'description', 'interest', 'date_of_birth', 'email', 'account', 'credit_card']);

        for ($i = 0; $i < 1000; $i++) {
            $row = [
                $name = fake()->name(),
                preg_replace('/[\r\n]+/', ' ', fake()->address()),
                'false',
                fake()->text(150),
                fake()->text(50),
                fake()->dateTime()->format('Y-m-d H:i:s'),
                fake()->email(),
                Str::random(8),
                json_encode([
                    "type" => "Visa",
                    "number" => "4929182424412",
                    "name" => $name,
                    "expirationDate" => "02/20"
                ])
            ];
            fputcsv($source, $row);
        }
        fclose($source);

        $this->assertTrue(true);
    }

    public function test_chunk_generator()
    {
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";
        $source = new ChunkGenerator($filePath, 'json');

        foreach($source->chunks() as $chunk) {
            var_dump($chunk);
            var_dump($source->getPosition());
        }

        $this->assertTrue(true);
    }

    public function test_date_time_format()
    {
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";
        $source = new ChunkGenerator($filePath, 'json');

        foreach($source->chunks() as $chunk) {
            var_dump(gettype($chunk->date_of_birth));
        }

        $this->assertTrue(true);
    }
}

