<?php
require __DIR__ . '/../vendor/autoload.php';

Eloquent\Asplode\Asplode::install();

use Skewd\Amqp\Connection\ClusterConnector;
use Skewd\Amqp\PhpAmqpLib\PalConnector;

$connector = ClusterConnector::create(
    PalConnector::create('localhost', 5672),
    PalConnector::create('localhost', 5673)
);

$connection = $connector->connect();
$channel = $connection->channel();
