<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\DataImporter;

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
}
