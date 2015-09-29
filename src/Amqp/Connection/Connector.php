<?php
namespace Skewd\Amqp\Connection;

/**
 * Establishes a connection to an AMQP server.
 */
interface Connector
{
    /**
     * Connect to an AMQP server.
     *
     * @return Connection          The AMQP connection.
     * @throws ConnectionException If the connection could not be established.
     */
    public function connect();
}
