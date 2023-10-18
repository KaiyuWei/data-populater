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
}
