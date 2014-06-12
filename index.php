<?php
require_once './vendor/autoload.php';
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Table\Models\Entity;
// Bootstrap with config
$app = new \Slim\Slim(['table_name' => 'osfi', 'conn_string' => getenv('CUSTOMCONNSTR_OSFI_CONN_STRING')]);
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());
// Custom methods
$app->timer = function() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
};
$app->container->singleton('tableClient', function () use ($app) {
    return ServicesBuilder::getInstance()->createTableService($app->config('conn_string'));
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
        $time_start = $app->timer;
        $result = $app->tableClient->queryEntities($app->config('table_name'), $filter);
        $time_end = $app->timer;
        $time = $time_end - $time_start;
    }
    catch(ServiceException $e){
        $app->render(500, [
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
        'time' => $time,
        'entities' => array_values($result_array),
    ]);
});

$app->run();
