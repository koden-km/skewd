<?php
namespace Skewd\Common\Lock;

use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Psr\Log\LoggerInterface;
use Skewd\Common\Node\Node;

/**
 * A lock manager that uses AMQP queues as the lockable resource.
 */
final class AmqpLockManager implements LockManager
{
    /**
     * Create an AMQP lock manager.
     *
     * @param Node            $node   The node that owns the acquired locks.
     * @param LoggerInterface $logger A logger to use for debug output.
     *
     * @return AmqpLockManager
     */
    public static function create(Node $node, LoggerInterface $logger)
    {
        return new self($node, $logger);
    }

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
    public function lock($resource)
    {
        try {
            $this->node->createChannel()->queue_declare(
                $this->queueName($resource),
                false, // passive
                false, // durable
                true   // exclusive
            );
        } catch (AMQPProtocolChannelException $e) {
            if (self::AMQP_RESOURCE_LOCKED_CODE !== $e->getCode()) {
                throw $e;
            }

            $this->logger->debug(
                'Failed to lock resource "{resource}"',
                ['resource' => $resource]
            );

            throw LockException::alreadyLocked($resource, $e);
        }

        $this->logger->debug(
            'Locked resource "{resource}"',
            ['resource' => $resource]
        );

        return ScopedLock::create(
            function () use ($resource) {
                $this->unlock($resource);
            }
        );
    }

    /**
     * Please note that this code is not part of the public API. It may be
     * changed or removed at any time without notice.
     *
     * @access private
     *
     * This constructor is public so that it may be used by auto-wiring
     * dependency injection containers. If you are explicitly constructing an
     * instance please use one of the static factory methods listed below.
     *
     * @see AmqpLockManager::create()
     *
     * @param Node            $node   The node that owns the acquired locks.
     * @param LoggerInterface $logger A logger to use for debug output.
     */
    public function __construct(Node $node, LoggerInterface $logger)
    {
        $this->node = $node;
        $this->logger = $logger;
    }

    /**
     * Unlock a resource.
     *
     * This method is invoked by the closure passed to the ScopedLock in lock().
     *
     * @param string $name The name of the resource to unlock.
     */
    private function unlock($resource)
    {
        try {
            $this->node->createChannel()->queue_delete(
                $this->queueName($resource)
            );

            $this->logger->debug(
                'Unlocked resource "{resource}"',
                ['resource' => $resource]
            );
        } catch (AMQPExceptionInterface $e) {
            $this->logger->debug(
                'Failed to unlock resource "{resource}" gracefully',
                [
                    'resource' => $resource,
                    'exception' => $e,
                ]
            );
        }
    }

    /**
     * Generate a queue name for a resource.
     *
     * @param string $resource
     *
     * @return string
     */
    private function queueName($resource)
    {
        return 'lock/' . $resource;
    }

    const AMQP_RESOURCE_LOCKED_CODE = 405;

    private $node;
    private $logger;
}
