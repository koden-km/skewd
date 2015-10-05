<?php
namespace Skewd\Lock;

use Skewd\Amqp\Connection\Connection;

/**
 * An AMQP based resource factory.
 */
final class AmqpResourceFactory implements ResourceFactory
{
    /**
     * Create an AMQP resource factory.
     *
     * @param Connection $connection The AMQP connection.
     *
     * @return AmqpResourceFactory
     */
    public static function create(Connection $connection)
    {
        return new self($connection);
    }

    /**
     * Create a resource.
     *
     * @param string $name The name of the resource.
     *
     * @return Lockable
     */
    public function createResource($name)
    {
        return new AmqpResource($this->connection, $name);
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
     * @see AmqpResourceFactory::create()
     *
     * @param Connection $connection The AMQP connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    private $connection;
}
