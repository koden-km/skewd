<?php
namespace Skewd\Common\Amqp;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;

/**
 * A connector creates low-level connections to an AMQP server.
 *
 * In this context "low-level" refers to the the connection object provided by
 * the "videlalvaro/php-amqplib" library.
 */
interface Connector
{
    /**
     * Connect to an AMQP server.
     *
     * @return AbstractConnection     The server connection.
     * @throws AMQPExceptionInterface The connection could not be established.
     */
    public function connect();
}
