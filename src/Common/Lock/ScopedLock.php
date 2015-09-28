<?php
namespace Skewd\Common\Lock;

/**
 * Represents a locked resource.
 *
 * When the scoped lock goes out of scope (ie, is destructed), the resource is
 * unlocked.
 */
final class ScopedLock
{
    /**
     * Create a scoped lock.
     *
     * @param callable $unlock The callable used to unlock the resource.
     *
     * @return ScopedLock
     */
    public static function create(callable $unlock)
    {
        return new self($unlock);
    }

    /**
     * Unlock the resource if it is still locked.
     */
    public function __destruct()
    {
        call_user_func($this->unlock);
    }

    /**
     * @param callable $unlock The callable used to unlock the resource.
     */
    private function __construct(callable $unlock)
    {
        $this->unlock = $unlock;
    }

    /**
     * A lock can not be cloned (as then there would be two things trying to
     * release) the lock.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    private $unlock;
}
