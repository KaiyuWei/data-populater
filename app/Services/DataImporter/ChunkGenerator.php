<?php
/**
 * This class takes a file and return a yielder with which you can iterate over the 
 * file and read data from it chunk by chunk.
 */
namespace App\Services\DataImporter;
use JsonMachine\Items;

class ChunkGenerator {
    /**
     * path to the file this generator is for
     * @var string
     */
    private $file;

    /**
     * the format of the file
     * @var string
     */
    private $format;

    /**
     * position pointer
     * @var int
     */
    private $position;

    public function __construct($file, $format) {
        $this->file = $file;
        $this->format = $format;
        $this->position = 0;
    }

    /**
     * gnerate chunks from the file
     */
    public function chunks() {
        switch ($this->format) {
            case 'json':
                yield from $this->jsonChunks();
                break;
            case 'csv':
                yield from $this->csvChunks();
                break;
            case 'xml':
                yield from $this->xmlChunks();
                break;
        }
    }

    /**
     * the generator for json files
     */
    private function jsonChunks() {
        $source = Items::fromFile($this->file, ['debug' =>true]);
        foreach ($source as $chunk) {
            // update the pointer position and yield a chunk
            $this->position = $source->getPosition();
            yield $chunk;
        }
    }

    private function csvChunks() {
        yield 1;
    }

    private function xmlChunks() {
        yield 1;
    }

    /**
     * get the position the pointer is in now
     */
    public function getPosition() {
        return $this->position;
    }
}