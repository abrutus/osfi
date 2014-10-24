<?php
require_once './vendor/autoload.php';
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Table\Models\Entity;
$conn_string = getenv('CUSTOMCONNSTR_OSFI_CONN_STRING');
$ts = ServicesBuilder::getInstance()->createTableService($conn_string);
$test = !empty($argv[1]) ? metaphone($argv[1]) : "ANTRBRTS";
echo $filter = "PartitionKey eq '$test' and (RowKey eq 'org:$test' or RowKey eq 'name:$test')";
$result=  $ts->queryEntities("osfi", $filter);
print_r($result);