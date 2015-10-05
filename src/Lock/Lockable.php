<?php
namespace Skewd\Lock;

/**
 * A resource that may be locked.
 */
interface Lockable
{
    /**
     * Acquire a lock on this object.
     *
     * @param LockMode $mode The lock mode to use.
     *
     * @return Lock          The acquired lock.
     * @throws LockException if the resource could not be locked.
     */
    public function acquireLock(LockMode $mode);

    /**
     * Attempt to acquire a lock on this object.
     *
     * @param LockMode $mode  The lock mode to use.
     * @param Lock     &$lock Assigned the acquired lock, if successful.
     *
     * @return boolean True if the lock was acquired; otherwise, false.
     */
    public function tryAcquireLock(LockMode $mode, Lock &$lock = null);
}
