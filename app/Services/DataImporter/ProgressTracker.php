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
    public function __construct(array $chunkBytes, JsonDataImportJob $job) {
      // array_values() makes sure that the keys are numbers
      $this->chunkBytes = $chunkBytes;
      // the job this tracker tracks
      $this->job = $job;
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
  }