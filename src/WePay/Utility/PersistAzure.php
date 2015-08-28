<?php
/**
 * Date: 2/17/15
 * Time: 6:58 PM
 * Andre Brutus
 */

namespace WePay\Utility;

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Table\Models\BatchOperations;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\Models\EdmType;

class PersistAzure {

    public $op_count;


    public $service;
    public $operations;
    const TABLE_NAME = "ofac";
    const BATCH_SIZE = 99;
    public $total = 0;
    public $rowkeys = [];

    public function __construct() {
        $CONN_STRING = getenv('CUSTOMCONNSTR_OSFI_CONN_STRING');
        $this->service = ServicesBuilder::getInstance()->createTableService($CONN_STRING);
        $this->operations = new BatchOperations();
    }
    public function add($firstLetter, $key, $payload) {
        $entity = new Entity;
        $entity->setPartitionKey("starts-with:$firstLetter");
        $entity->setRowKey($key);
        $entity->addProperty("match", EdmType::STRING, json_encode($payload));
        // cant have two duplicate rowkeys in the same batch
        if(!isset($this->rowkeys[$key])) {
            $this->operations->addInsertOrReplaceEntity(self::TABLE_NAME, $entity);
            $this->rowkeys[$key] = true;
        }
        if(++$this->op_count>= self::BATCH_SIZE) {
            $this->flush();
        }
        echo ++$this->total . " - Adding $key\n";
    }
    public function flush() {
        try {
        $this->service->batch($this->operations);
        $this->op_count = 0;
        $this->operations = new BatchOperations();
        echo $this->total . " Flushing \n";
        } catch(\Exception $e) {
            echo "Failed " . $e;
            print_r($this->operations);
        }
    }
    public function __destruct() {
        if($this->op_count > 0) {
            $this->flush();
        }
    }
}