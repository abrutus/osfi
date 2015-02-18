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


class Consolidated extends CsvStructure implements Indexable {
    public $prefix = "sdn";
    /**
     * Column
     * sequence Column name  Type     Size  Description
     * -------- ------------ -------  ----  ---------------------
     * 1        ent_num     number          unique record
     * identifier/unique
     * listing identifier
     * 2        SDN_Name     text     350   name of SDN
     * 3        SDN_Type     text     12    type of SDN
     * 4        Program      text     50    sanctions program name
     * 5        Title        text     200   title of an individual
     * 6        Call_Sign    text     8     vessel call sign
     * 7        Vess_type    text     25    vessel type
     * 8        Tonnage      text     14    vessel tonnage
     * 9        GRT          text     8     gross registered tonnage
     * 10       Vess_flag    text     40    vessel flag
     * 11       Vess_owner   text     150   vessel owner
     * 12       Remarks      text     1000  remarks on SDN*
     */
    /**
     * sequence Column name  Type     Size  Description
    -------- ------------ -------  ----  ---------------------
    1        ent_num     number          unique record
    identifier/unique
    listing identifier
    2        SDN_Name     text     350   name of entry
    3        SDN_Type     text     12    type of entry
    4        Program      text     50    sanctions program name
    5        Title        text     200   title of an individual
    6        Call_Sign    text     8     vessel call sign
    7        Vess_type    text     25    vessel type
    8        Tonnage      text     14    vessel tonnage
    9        GRT          text     8     gross registered tonnage
    10       Vess_flag    text     40    vessel flag
    11       Vess_owner   text     150   vessel owner
    12       Remarks      text     1000  remarks on entry*
     */
    const NULL = '-0-';
    public $ent_num, $SDN_Name, $SDN_Type, $Program, $Title, $Call_Sign, $Vess_type, $Tonnage, $GRT, $Vess_flag, $Vess_owner, $Remarks;
    public $fields = ['ent_num','SDN_Name', 'SDN_Type', 'Program', 'Title', 'Call_Sign', 'Vess_type', 'Tonnage', 'GRT', 'Vess_flag', 'Vess_owner', 'Remarks'];
    public $index_columns = [
        'SDN_Name'
    ];
    public function getIndex() {
        $initial_index = [];
        foreach ($this->index_columns as $index) {
            $initial_index [] = $this->{$index};
        }
        return join(" ", $initial_index);
    }
}

