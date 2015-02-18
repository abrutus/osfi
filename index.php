<?php
require_once './vendor/autoload.php';
use WePay\Service\Ofac;
use WePay\Service\Osfi;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Common\ServicesBuilder;
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

$app->get('/osfi/exactname/:name', function ($name) use ($app) {
    $time_start = $app->timer;
    $result_array = Osfi::query(Osfi::TYPE_EXACT, $app->tableClient, 'osfi', $name);
    $time_end = $app->timer;
    $time = $time_end - $time_start;

    $app->render(200, ['count' => count($result_array), 'time' => $time, 'entities' => $result_array, ]);
});


$app->get('/ofac/exactname/:name', function ($name) use ($app) {
    $time_start = $app->timer;
    $result_array = Ofac::query(Ofac::TYPE_EXACT, $app->tableClient, 'ofac', $name);
    $time_end = $app->timer;
    $time = $time_end - $time_start;

    $app->render(200, ['count' => count($result_array), 'time' => $time, 'entities' => $result_array, ]);
});

// Legacy routes to be deprecated next version
$app->get('/exactname/:name', function ($name) use ($app) { $app->redirect('/osfi/exactname/' . $name, 301); });
$app->get('/metaphone/:name', function ($name) use ($app) { $app->redirect('/osfi/exactname/' . $name, 301); });
$app->run();
