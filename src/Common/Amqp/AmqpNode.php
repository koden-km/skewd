<?php
namespace Skewd\Common\Amqp;

use Icecave\Isolator\IsolatorTrait;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Common\Messaging\Channel;
use Skewd\Common\Messaging\ConnectionException;
use Skewd\Common\Messaging\HexNodeIdGenerator;
use Skewd\Common\Messaging\Node;
use Skewd\Common\Messaging\NodeIdGenerator;

/**
 * A node that uses "videlalvaro/php-amqplib" to communicate with an AMQP server.
 */
final class AmqpNode implements Node
{
    /**
     * Create an AMQP node.
     *
     * @param Connector            $connector       The connector used to establish AMQP connections.
     * @param NodeIdGenerator|null $nodeIdGenerator The ID generator to use upon connection.
     *
     * @return AmqpNode
     */
    public static function create(
        Connector $connector,
        NodeIdGenerator $nodeIdGenerator = null
    ) {
        return new self($connector, $nodeIdGenerator);
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
        if (null === $this->nodeId) {
            return null;
        } elseif ($this->connection->isConnected()) {
            return $this->nodeId;
        }

        $this->nodeId = null;

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
            $nodeId = $this->generateNodeId($connection);
        } catch (AMQPExceptionInterface $e) {
            throw ConnectionException::create($e);
        }

        $this->connection = $connection;
        $this->nodeId = $nodeId;
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
            $this->nodeId = null;
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
        return $this->isolator()->new(
            AmqpChannel::class,
            $this->connection->channel()
        );
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
     * @param Connector            $connector       The connector used to establish AMQP connections.
     * @param NodeIdGenerator|null $nodeIdGenerator The ID generator to use upon connection.
     */
    public function __construct(
        Connector $connector,
        NodeIdGenerator $nodeIdGenerator = null
    ) {
        $this->connector = $connector;
        $this->nodeIdGenerator = $nodeIdGenerator ?: HexNodeIdGenerator::create();
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
        $ids = $this->nodeIdGenerator->generate(self::RESERVATION_ATTEMPTS);

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
    const AMQP_RESOURCE_LOCKED_CODE = 405;

    use IsolatorTrait;

    /**
     * @var Connector The connector used to establish AMQP connections.
     */
    private $connector;

    /**
     * @var $nodeIdGenerator The ID generator to use upon connection.
     */
    private $nodeIdGenerator;

    /**
     * @var AMQPConnection|null The current AMQP connection, or null if disconnected.
     */
    private $connection;

    /**
     * @var string|null The node's ID, or null if disconnected.
     */
    private $nodeId;
}
