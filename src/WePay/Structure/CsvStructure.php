<?php
/**
 * Date: 2/12/15
 * Time: 1:08 PM
 * Andre Brutus
 */

namespace WePay\Structure;


class CsvStructure {
    public $prefix = "";
    const NULL = '-0-';
    public $fields = [];
    public $index_columns = [];
    public function json() {
        $output = [];
        foreach($this->fields as $k) {
            if(property_exists($this, $k)) {
                $output[$k] = $this->{$k};
            }
        }
        return json_encode($output);
    }
    public function __construct(array $values) {
        foreach($this->fields as $k=>$v) {
            if(isset($values[$k])) {
                if(property_exists($this, $v)) {
                    // CSV determines null as
                    $value = trim($values[$k]);
                    if($value != self::NULL) {
                        $this->{$v} = $value;
                    }
                }
            }
        }
    }
}