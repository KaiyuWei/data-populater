<?php
/**
 * a class that follows the progress of reading and processing data from a file
 */
namespace App\Services\DataImporter;

use App\Jobs\JsonDataImportJob;

class ProgressTracker implements \Iterator {
    /**
     * the job this tracker tracks
     * @var JsonDataImportJob
     */
    private $job;

    /**
     * the point in the file where the job starts
     * @var int
     */
    private $start;

    /**
     * Bytes of chunks to be processed by the job bind to this tracker
     * @var array
     */
    private $chunkBytes = [];

    /**
     * the current position in the array representing the chunck being processed
     */
    private $pointer = 0;
  
    /**
     * Constructor
     * @param array the bytes of chunks in the job
     */
    public function __construct(array $chunkBytes, JsonDataImportJob $job, int $start) {
        // array_values() makes sure that the keys are numbers
        $this->chunkBytes = $chunkBytes;
        
        // the job this tracker tracks
        $this->job = $job;
        
        // the start point of the job
        $this->start = $start;
    }

    /**
     * get the bytes of the chunck currently being processed
     */
    public function current() {
      return $this->chunkBytes[$this->pointer];
    }
  
    /**
     * the index of the bytes of the chunck currently being processed
     */
    public function key() {
      return $this->pointer;
    }

    /**
     * access a chunck size in the chunkBytes array
     */
    public function chunkSize(int $index) {
        return $this->chunkBytes[$index];
    }
  
    public function next(): void {
      $this->pointer++;
    }
  
    public function rewind(): void {
      $this->pointer = 0;
    }
  
    /**
     * is the pointer valid?
     */
    public function valid(): bool {
      // count() indicates how many items are in the list
      return $this->pointer < count($this->chunkBytes);
    }

    /**
     * expose the chunkBytes array
     */
    public function chunks() {
        return $this->chunkBytes;
    }

    /**
     * The bytes that have been processed
     * @return int the processed bytes in this chunk
     */
    public function processedBytes() {
        $processedBytes = 0;
        // loop over the chunkBytes array from the beginning until the current one (excluded)
        for ($i = 0; $i < $this->key(); $i++) {
            $processedBytes += $this->chunkBytes[$i];
        }
        return $processedBytes;
    }
  }