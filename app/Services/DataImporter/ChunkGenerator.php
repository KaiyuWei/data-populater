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
     * the type format of the file, json, csv or xml
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
     * generate chunks from json files
     */
    private function jsonChunks() {
        $source = Items::fromFile($this->file, ['debug' =>true]);
        foreach ($source as $chunk) {
            // preprocess data in the chunk
            $chunk = $this->preprocess($chunk);

            // update the pointer position and yield a chunk
            $this->position = $source->getPosition();

            yield $chunk;
        }
    }

    private function csvChunks() {
        // to be completed for csv files
        yield 1;
    }

    private function xmlChunks() {
        // to be completed for xml files
        yield 1;
    }

    /**
     * get the position the pointer is in now
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * preprocess data so the data format follow the sql data standard
     * @param \stdClass the chunk to be pre-processed
     */
    private function preprocess($chunk){
        // preprocess the boolean value. 
        $chunk->checked = $chunk->checked ? 1 : 0;

        // preprocess the datatime values
        // the format 'dd/mm/yyyy' cannot be recognised by SQL datetime datatype
        if (!is_null($dateTime = $chunk->date_of_birth) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateTime)) {
            // convert it to sql datetime format
            $chunk->date_of_birth = \DateTime::createFromFormat('d/m/Y', $dateTime)->format('Y-m-d H:i:s');
        }

        // preprocess the json values
        if ($card = $chunk->credit_card)  $chunk->credit_card = json_encode($card);
        
        // preprocess any null values
        foreach ($chunk as $key => $value) {
            if (is_null($value)) $chunk->$key = null;
        }

        return $chunk;
    }
}