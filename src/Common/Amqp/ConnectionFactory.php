<?php
namespace Skewd\Common\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Create a connection to an AMQP server.
 */
interface ConnectionFactory
{
    /**
     * Open a new connection to the AMQP server.
     *
     * @return AMQPStreamConnection
     */
    public function create();
}
