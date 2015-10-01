<?php
namespace Skewd\Common\Lock;

use Exception;
use RuntimeException;

/**
 * Indicates an error while locking a resource.
 */
final class LockException extends RuntimeException
{
    /**
     * Create a lock exception that indicates a failure to lock due to the
     * resource already being locked.
     *
     * @param string         $resource The resource being locked.
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return LockException
     */
    public static function alreadyLocked($resource, Exception $previous = null)
    {
        return new self(
            'Failed to lock resource "' . $resource . '", resource is already locked.',
            0,
            $previous
        );
    }
}
