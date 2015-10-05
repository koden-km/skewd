<?php
namespace Skewd\Amqp;

use Exception;
use RuntimeException;

/**
 * The client attempted to work with a server resource that no longer exists.
 */
final class ResourceNotFoundException extends RuntimeException
{
    /**
     * A queue could not be found.
     *
     * @param string         $name     The name of the queue.
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return ResourceNotFoundException
     */
    public static function queueNotFound($name, Exception $previous = null)
    {
        return new self(
            'Queue "' . $name . '" does not exist.',
            0,
            $previous
        );
    }
}
