<?php
/**
 * a class that follows the progress of reading and processing data from a file
 */
namespace App\Services\DataImporter;

use App\Jobs\DataImportJob;

class ProgressTracker implements \Iterator {
    /**
     * the job this tracker tracks
     * @var DataImportJob
     */
    private $job;

    /**
     * the point in the file where the job starts 
     * it is an absolute position index of the area that this job and tracker processes
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
    public function __construct(array $chunkBytes, DataImportJob $job, int $start) {
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
     * The position of the current chunk in the whole file
     * @return int the processed bytes in this chunk
     */
    public function bytesAhead() {
        // the bytes that have been processed in the batch
        $processedBytes = 0;
        // loop over the chunkBytes array from the beginning until the current one (excluded)
        for ($i = 0; $i < $this->key(); $i++) {
            $processedBytes += $this->chunkBytes[$i];
        }

        // the position of this chunk in the file + the bytes that have been processed in this batch.
        return $this->start + $processedBytes;
    }
  }