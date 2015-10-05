<?php
namespace Skewd\Lock;

use Exception;
use RuntimeException;

/**
 * Indicates a failed attempt to acquire a lock.
 */
final class LockException extends RuntimeException
{
    /**
     * Create a lock exception that indicates a failure to lock due to the
     * resource already being locked.
     *
     * @param string         $resource A description of the resource.
     * @param LockMode       $mode     The locking mode used.
     * @param Exception|null $previous The exception that caused this exception, if any.
     *
     * @return LockException
     */
    public static function alreadyLocked(
        $resource,
        LockMode $mode,
        Exception $previous = null
    ) {
        return new self(
            sprintf(
                'Failed to acquire %s lock on "%s", resource is already locked.',
                $mode,
                $resource
            ),
            0,
            $previous
        );
    }
}
