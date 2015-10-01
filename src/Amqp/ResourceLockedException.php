<?php
namespace Skewd\Amqp;

use Exception;
use RuntimeException;

/**
 * The client attempted to work with a server resource to which it has no access
 * because another connection is using it.
 */
final class ResourceLockedException extends RuntimeException
{
    /**
     * Create a queue-related resource locked exception.
     *
     * @param string         $name     The name of the queue.
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return DeclareException
     */
    public static function queueIsExclusive($name, Exception $previous = null)
    {
        return new self(
            'Failed to declare queue "' . $name . '", another connection has exclusive access.',
            0,
            $previous
        );
    }
}
