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
    const BATCH_SIZE = 100;
    public function __construct() {
        $CONN_STRING = getenv('CUSTOMCONNSTR_OSFI_CONN_STRING');
        $this->service = ServicesBuilder::getInstance()->createTableService($CONN_STRING);
        $this->operations = new BatchOperations();
    }
    public function add($firstLetter, $key, $payload) {
        $entity = new Entity;
        // set metaphone of the organization name as the partition key (index).
        $entity->setPartitionKey("starts-with:$firstLetter");
        $entity->setRowKey($key);
        $entity->addProperty("match", EdmType::STRING, json_encode($payload));
        $this->operations->addInsertOrReplaceEntity(self::TABLE_NAME, $entity);
        if(++$this->op_count>= self::BATCH_SIZE) {
            $this->flush();
        }
    }
    public function flush() {
        $this->service->batch($this->operations);
        $this->op_count = 0;
        $this->operations = new BatchOperations();

    }
    public function __destruct() {
        if($this->op_count > 0) {
            $this->flush();
        }
    }
}