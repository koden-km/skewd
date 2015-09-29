<?php
namespace Skewd\Common\Node;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Common\Lock\LockManager;

final class AmqpNode implements Node
{
    /**
     * Create an AMQP node.
     *
     * @param Connector        $connector   The connector used to establish AMQP connections.
     * @param IdGenerator|null $idGenerator The ID generator to use upon connection, or null to use the default generator.
     *
     * @return AmqpNode
     */
    public static function create(
        Connector $connector,
        IdGenerator $idGenerator = null
    ) {
        return new self($connector, $idGenerator);
    }

    /**
     * Get this node's unique ID.
     *
     * The node ID is only available when connected to an AMQP server.
     *
     * @return string|null The node ID if connected; otherwise, null.
     */
    public function id()
    {
        if (null === $this->id) {
            return null;
        } elseif ($this->connection->isConnected()) {
            return $this->id;
        }

        $this->id = null;

        return null;
    }

    /**
     * Connect to the AMQP server.
     *
     * If a connection has already been established it is first disconnected.
     *
     * @throws ConnectionException The connection could not be established.
     */
    public function connect()
    {
        $this->disconnect();

        try {
            $connection = $this->connector->connect();

            $ids = $this->idGenerator->generate(self::RESERVATION_ATTEMPTS);

            foreach ($id as $id) {
            }

            $nodeId = $this->generateNodeId($connection);
        } catch (AMQPExceptionInterface $e) {
            throw ConnectionException::create($e);
        }

        $this->connection = $connection;
        $this->id = $id;
        $this->lockManager = AmqpLockManager::create($this, $this->logger);
    }

    /**
     * Disconnect from the AMQP server.
     */
    public function disconnect()
    {
        try {
            if (!$this->connection) {
                return;
            } elseif ($this->connection->isConnected()) {
                $this->connection->close();
            }
        } finally {
            $this->connection = null;
            $this->id = null;
            $this->lockManager = null;
        }
    }

    /**
     * Check if there is currently a connection to the AMQP server.
     *
     * @return boolean True if currently connected; otherwise, false.
     */
    public function isConnected()
    {
        if ($this->connection) {
            return $this->connection->isConnected();
        }

        return false;
    }

    /**
     * Create an AMQP channel.
     *
     * @return Channel The newly created channel.
     */
    public function createChannel()
    {
        return new AmqpChannel(
            $this->connection->channel()
        );
    }

    /**
     * Get the node's lock manager.
     *
     * @return LockManager The lock manager.
     */
    public function lockManager()
    {
        if (null === $this->lockManager) {
            $this->lockManager = AmqpLockManager::create($this, $this->logger);
        }

        return $this->lockManager;
    }

    /**
     * Wait for network activity.
     *
     * @param integer|float $timeout The number of seconds to wait for technology.
     *
     * @return boolean True if the operation was interrupted by a signal; otherwise, false.
     */
    public function wait($timeout)
    {
        @usleep(intval($timeout * 1000000));

        return false;
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
     * @see AmqpNode::create()
     *
     * @param Connector        $connector   The connector used to establish AMQP connections.
     * @param IdGenerator|null $idGenerator The ID generator to use upon connection.
     */
    public function __construct(
        Connector $connector,
        IdGenerator $idGenerator = null
    ) {
        $this->connector = $connector;
        $this->idGenerator = $idGenerator ?: HexIdGenerator::create();
    }

    /**
     * Generate and reserve an ID for use by this node.
     *
     * @param AbstractConnection $connection
     *
     * @return string The node ID.
     */
    private function generateNodeId(AbstractConnection $connection)
    {
        $ids = $this->idGenerator->generate(self::RESERVATION_ATTEMPTS);

        foreach ($ids as $id) {
            $channel = $connection->channel();

            try {
                $channel->queue_declare(
                    'node-' . $id,
                    false, // passive
                    false, // durable
                    true   // exclusive
                );

                return $id;
            } catch (AMQPExceptionInterface $e) {
                // Immediately re-throw the error if it is anything other than
                // a failure to acquire the exclusive queue ...
                if ($e->getCode() !== self::AMQP_RESOURCE_LOCKED_CODE) {
                    break;
                }
            } finally {
                $channel->close();
            }
        }

        throw $e;
    } // @codeCoverageIgnore

    const RESERVATION_ATTEMPTS = 5;

    /**
     * @var Connector The connector used to establish AMQP connections.
     */
    private $connector;

    /**
     * @var $idGenerator The ID generator to use upon connection.
     */
    private $idGenerator;

    /**
     * @var AMQPConnection|null The current AMQP connection, or null if disconnected.
     */
    private $connection;

    /**
     * @var string|null The node's ID, or null if disconnected.
     */
    private $id;
}
