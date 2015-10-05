<?php
namespace Skewd\Lock;

use Skewd\Amqp\Connection\Connection;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\QueueParameter;
use Skewd\Amqp\ResourceLockedException;
use Skewd\Amqp\ResourceNotFoundException;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * A lockable resource based on an AMQP queue/consumer.
 */
final class AmqpResource implements Lockable
{
    /**
     * @param Connection $connection The AMQP connection.
     * @param string     $name       The resource name.
     */
    public function __construct(Connection $connection, $name)
    {
        $this->connection = $connection;
        $this->name = $name;
    }

    /**
     * Acquire a lock on this object.
     *
     * @param LockMode $mode The lock mode to use.
     *
     * @return Lock          The acquired lock.
     * @throws LockException if the resource could not be locked.
     */
    public function acquireLock(LockMode $mode)
    {
        // Prepare consumer parameters based on the lock mode ...
        if ($mode === LockMode::EXCLUSIVE()) {
            $parameters = [ConsumerParameter::EXCLUSIVE()];
        } else {
            $parameters = [];
        }

        while (true) {
            // Declare a (non-exclusive) queue to act as our lockable resource ...
            $queue = $this->connection->channel()->queue(
                'lock.' . $this->name,
                [QueueParameter::AUTO_DELETE()]
            );

            // Attempt to begin consuming from the queue. The queue isn't actually
            // intended to delivery messages, so they are just black-holed with an
            // empty closure ...
            try {
                $consumer = $queue->consume(
                    function () {},
                    $parameters
                );

                break;

            // The queue does not exist, most likely another consumer has been
            // created and cancelled and the queue has been auto-deleted ...
            } catch (ResourceNotFoundException $e) {
                continue;

            // The queue already has an exclusive consumer ...
            } catch (ResourceLockedException $e) {
                throw LockException::alreadyLocked(
                    $this->name,
                    $mode,
                    $e
                );
            }
        }

        return Lock::create(
            $mode,
            [$consumer, 'cancel']
        );
    }

    /**
     * Attempt to acquire a lock on this object.
     *
     * @param LockMode $mode  The lock mode to use.
     * @param Lock     &$lock Assigned the acquired lock, if successful.
     *
     * @return boolean True if the lock was acquired; otherwise, false.
     */
    public function tryAcquireLock(LockMode $mode, Lock &$lock = null)
    {
        try {
            $lock = $this->acquireLock($mode);
        } catch (LockException $e) {
            $lock = null;
        }

        return null !== $lock;
    }

    private $connection;
    private $name;
}
