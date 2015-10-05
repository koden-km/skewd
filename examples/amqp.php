<?php
require __DIR__ . '/../vendor/autoload.php';

Eloquent\Asplode\Asplode::install();

use Icecave\Stump\Logger;
use Skewd\Amqp\Connection\ConnectionWaitResult;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\Message;
use Skewd\Amqp\PhpAmqpLib\PalConnector;
use Skewd\Amqp\QueueParameter;

$connector  = PalConnector::create(
    getenv('AMQP_HOST') ?: 'localhost',
    getenv('AMQP_PORT') ?: 5672
);

$connection = $connector->connect();
$channel = $connection->channel();
