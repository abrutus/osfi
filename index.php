<?php
require_once './vendor/autoload.php';
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Table\Models\Entity;
// Bootstrap
$app = new \Slim\Slim();
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());
$app->conn_string = getenv('CUSTOMCONNSTR_OSFI_CONN_STRING');
$app->table_name = "osfi";
$app->container->singleton('tableClient', function () use ($app) {
    return ServicesBuilder::getInstance()->createTableService($app->conn_string);
});

// Name route
$app->get('/name/:name', function ($name) use ($app) {
      $app->redirect('/metaphone/' . metaphone($name));
});

// Metaphone route
$app->get('/metaphone/:name', function ($name) use ($app) {
    $filter = "PartitionKey eq '" . trim($name) . "' ";
    $filter.= "or PartitionKey eq 'org:" . trim($name) . "'";

    try {
        $result = $app->tableClient->queryEntities($app->table_name, $filter);
    }
    catch(ServiceException $e){
        $app->render(200, [
            'code' => $e->getCode(),
            'msg' => $e->getMessage(),
            ]);
        return;
    }

    $entities = $result->getEntities();
    $result_array = [];
    // multiple results per key, remove duplicates by hashing to the unique id
    foreach ($entities as $entity) {
        $parsed = json_decode(utf8_decode($entity->getPropertyValue("match")));
        $id = current(explode(".", current($parsed)));
        $result_array[$id] = $parsed;
    }
    $app->render(200, [
        'count' => count($entities),
        'entities' => array_values($result_array),
    ]);
});

$app->run();
