<?php
/**
 * The filter for DataImportJob
 */
namespace App\Jobs\DataImportJobFilter;

class DataImportJobFilter {
    /**
     * array of filter function names
     * @var array<string>
     */
    private const FILTERS = ['age'];

    public function __construct() {
        //
    }

    /**
     * the array of bool values indicates of the corresponding row should be processed
     * @param array the data to be processed with this filter
     * @param array the filters to be used and their parameters. 
     *              e.g. ['age' => [25, 50, false]]
     * @return array<bool>
     */
    public function generateFilterArray($data, $selectedFilters = []) {
        // no filters used, return true
        if (empty($selectedFilters)) return array_fill(0, count($data), true);

        $filterResult = [];
        foreach($data as $row) {
            $useThisRow = true;
            // iterate over all filters
            foreach(self::FILTERS as $filter) {
                if (isset($selectedFilters[$filter])) {
                    $useThisRow = call_user_func_array([$this, $filter], array_merge([$row], $selectedFilters[$filter]));
                    
                    // if any false value, return false for this row
                    if(!$useThisRow) break;
                }
            }  
            $filterResult[] = $useThisRow;
        }
        return $filterResult;
    }

    /**
     * set the filter about client age for whom we process the data
     * @param array the data to be processed
     * @param int the min age of clients
     * @param int the max age of clients
     * @param bool should we include the data that age data is missing
     */
    public function age($row, $min, $max, $incudeNull = true){
        // handle null values
        if (is_null($dateOfBirth = $row['date_of_birth'])) return $incudeNull;

        // parse the dateof birth info to an array
        $dateOfBirth = date_parse($dateOfBirth);

        if ($dateOfBirth !== false) {
            // Create a DateTime object for the date of birth
            $birthDate = new \DateTime();
            $birthDate->setDate($dateOfBirth['year'], $dateOfBirth['month'], $dateOfBirth['day']);
        
            // Get the current date
            $currentDate = new \DateTime();
        
            // Calculate the difference in years
            $age = $currentDate->diff($birthDate)->y;
            return $age >= $min && $age <= $max;
        }
        else throw new \Exception('the date of birth string is in an unrecognisable format');
    }
}