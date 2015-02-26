<?php
/**
 * Date: 2/12/15
 * Time: 12:13 PM
 * Andre Brutus
 */

namespace WePay\Structure;


/**
 * Class Index
 * @package WePay\Structure
 *
 * This class contains all the indices we will create when persisting a certain entity
 */
class Index {
    public $indices = [];
    const SEPARATOR = "-";

    public function  __construct(array $initial= [])
    {
        $this->indices = $this->flattenArray($initial);
    }

    public function flattenArray(array $array = [])
    {
        $flattened = [];
        foreach($array as $index_group) {
            if(!is_array($index_group)) {
                $index_group = [$index_group];
            }
            $flattened [] = join(self::SEPARATOR, $index_group);

        }
        return $flattened;
        // Remove empty, retain unique after merging
        //$this->indices = array_unique(array_filter(array_merge($this->indices, $flattened)));
    }

    public function getIndices() {
        return $this->indices;
    }
}