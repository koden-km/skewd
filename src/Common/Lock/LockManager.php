<?php
namespace Skewd\Common\Lock;

/**
 * Locks resources for exclusive use.
 */
interface LockManager
{
    /**
     * Lock a resource for exclusive use.
     *
     * The return value is a ScopedLock. The resource remains locked until the
     * ScopedLock is destructed.
     *
     * @param string $name The name of the resource to lock.
     *
     * @return ScopedLock    An object that manages the lifetime of the lock.
     * @throws LockException if the resource could not be locked.
     */
    public function lock($resource);
}
