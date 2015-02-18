<?php
require_once 'D:/home/site/wwwroot/vendor/autoload.php';
use Symfony\Component\Console\Application;
$app = new Application('ofac updater', '1.0 (beta)');
$reload_ofac = new \WePay\Command\ReloadOfac();
$app->add($reload_ofac);
$app->setDefaultCommand($reload_ofac->getName());
$app->run();
