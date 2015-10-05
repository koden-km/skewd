<?php
namespace Skewd\Lock;

use LogicException;

/**
 * A previously-acquired lock on a resource.
 *
 * The lock is released upon destruction, unless release() or detach() is
 * called.
 *
 * @see Lock::release()
 * @see Lock::detach()
 */
final class Lock
{
    /**
     * Create a lock object.
     *
     * This method does not actually acquire the lock, instead use one of the
     * acquire methods on the Lockable interface.
     *
     * @see Lockable
     *
     * @param LockMode $mode    The lock mode used when the lock was acquired.
     * @param callable $release A callable used to release the lock.
     *
     * @return Lock
     */
    public static function create(LockMode $mode, callable $release)
    {
        return new self($mode, $release);
    }

    /**
     * Get the lock mode when the lock was acquired.
     *
     * @return LockMode The lock mode.
     */
    public function mode()
    {
        return $this->mode;
    }

    /**
     * Release the lock immediately.
     */
    public function release()
    {
        if ($this->detached) {
            throw new LogicException(
                'Can not release lock, it has been detached.'
            );
        } elseif (!$this->release) {
            throw new LogicException(
                'Can not release lock, it has already been released.'
            );
        }

        $release = $this->release;
        $this->release = null;
        $release();
    }

    /**
     * Detach the lock.
     *
     * The Lock object will no longer release the lock upon destruction. It is
     * the responsibility of the caller to ensure that the lock is released by
     * invoking the returned callable.
     *
     * @return callable|null A callable that releases the lock when invoked, or null if the lock was already detached.
     */
    public function detach()
    {
        if ($this->detached) {
            throw new LogicException(
                'Can not detach lock, it has already been detached.'
            );
        } elseif (!$this->release) {
            throw new LogicException(
                'Can not detach lock, it has been released.'
            );
        }

        $release = $this->release;
        $this->release = null;
        $this->detached = true;

        return $release;
    }

    /**
     * @param LockMode $mode    The lock mode used when the lock was acquired.
     * @param callable $release A callable used to release the lock.
     */
    private function __construct(LockMode $mode, callable $release)
    {
        $this->mode = $mode;
        $this->release = $release;
        $this->detached = false;
    }

    /**
     * Release the lock, if not already released or detached.
     */
    public function __destruct()
    {
        if ($this->release) {
            call_user_func($this->release);
        }
    }

    /**
     * Prevent cloning, otherwise there could be multiple objects attempting to
     * release the lock upon destruction.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    private $mode;
    private $release;
    private $detached;
}
