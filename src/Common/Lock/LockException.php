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
            $previous
        );
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
     * @see LockException::acquisitionFailed()
     *
     * @param string         $message  A description of the error.
     * @param Exception|null $previous The exception that caused this exception, if any.
     */
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
