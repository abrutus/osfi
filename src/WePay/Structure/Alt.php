<?php
/**
 * Date: 2/12/15
 * Time: 12:22 PM
 * Andre Brutus
 */

namespace WePay\Structure;
use WePay\Interfaces\Indexable;

/**
 * Class Sdn
 * @package WePay\Structure
 */


class Alt extends CsvStructure implements Indexable {
    public $prefix = "alt";
    /**
     * Column
    sequence Column name  Type     Size  Description
    -------- ------------ -------  ----  ---------------------
    1        ent_num      number         link to unique listing
    2        alt_num      number         unique record identifier
    3        alt_type     text     8     type of alternate identity
    (aka, fka, nka)
    4        alt_name     text     350   alternate identity name
    5        alt_remarks  text     200   remarks on alternate identity
     */
    public $ent_num, $alt_num, $alt_type, $alt_name, $alt_remarks;

    public $fields = ['ent_num','alt_num', 'alt_type', 'alt_name', 'alt_remarks'];
    public $index_columns = [
        'alt_name'
    ];
    public function getIndex() {
        $initial_index = [];
        foreach ($this->index_columns as $index) {
            $initial_index [] = $this->{$index};
        }
        return join(" ", $initial_index);
    }

}

