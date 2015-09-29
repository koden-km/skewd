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
        return new self('Unable to connect to AMQP server.', $previous);
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
        return new self('Disconnected from AMQP server.', $previous);
    }

    /**
     * Please note that this code is not part of the public API. It may be
     * changed or removed at any time without notice.
     *
     * @access private
     *
     * This constructor is only public because PHP does not let you make
     * exception constructors private. If you are explicitly constructing an
     * instance please use one of the static factory methods listed below.
     *
     * @see ConnectionException::couldNotConnect()
     * @see ConnectionException::notConnected()
     *
     * @param string         $message  A description of the error.
     * @param Exception|null $previous The exception that caused this exception, if any.
     */
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
