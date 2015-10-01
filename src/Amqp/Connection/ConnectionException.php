<?php
namespace Skewd\Amqp\Connection;

use Exception;
use RuntimeException;

/**
 * An error occured while attempting to establish an AMQP connection.
 */
final class ConnectionException extends RuntimeException
{
    /**
     * Create an exception that indicates a failure to establish a connection to
     * an AMQP server.
     *
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return ConnectionException
     */
    public static function couldNotConnect(Exception $previous = null)
    {
        return new self('Unable to connect to AMQP server.', 0, $previous);
    }

    /**
     * Create an exception that indicates the disconnection of a connection that
     * is expected to already be established.
     *
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return ConnectionException
     */
    public static function notConnected(Exception $previous = null)
    {
        return new self('Disconnected from AMQP server.', 0, $previous);
    }
}
