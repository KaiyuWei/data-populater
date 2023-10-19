<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\DataImporter;
use App\Jobs\RemoveJsonDebrisJob;
use App\Models\ChunkDebris;
use App\Models\Client;

class DataImportJobTest extends TestCase
{

    /**
     * test if data is written into the database by JsonDataImportJob
     */
    public function test_job_write_data_to_database(): void
    {
        // the json file we use for testing
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importJSON($filePath);

        // data should be there.
        $this->assertDatabaseHas("clients", [
            "name" => "Prof. Simeon Green"
        ]);
    }

    /**
     * test if the event can be triggered
     */
    public function test_job_fail_event(): void
    {
        // the json file we use for testing
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importJSON($filePath);

        // data should be there.
        $this->assertDatabaseHas("clients", [
            "name" => "Prof. Simeon Green"
        ]);
    }

    public function test_job_tracker(): void
    {
        // the json file we use for testing
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importJSON($filePath);

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

        DataImporter::importJSON($filePath);
        
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
     * 3. and delete from client.
     */
    public function test_data_importer_import_json_function_for_an_debris_file(): void
    {
        $filePath = "/Users/kaiyuwei/Downloads/shorter.json";

        DataImporter::importJSON($filePath);

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
}
