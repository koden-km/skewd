<?php
namespace Skewd\Common\Messaging;

use Exception;
use RuntimeException;

/**
 * An error occured while attempting to establish an AMQP connection.
 */
final class ConnectionException extends RuntimeException
{
    /**
     * Create a connection exception.
     *
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return ConnectionException
     */
    public static function create(Exception $previous = null)
    {
        return new self('Unable to establish AMQP connection.', $previous);
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
     * @see ConnectionException::create()
     *
     * @param string         $message  A description of the error.
     * @param Exception|null $previous The exception that caused this exception, if any.
     */
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
