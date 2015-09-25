<?php
namespace Skewd\Common\Amqp;

use Icecave\Isolator\IsolatorTrait;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Common\Messaging\Channel;
use Skewd\Common\Messaging\ConnectionException;
use Skewd\Common\Messaging\Node;

/**
 * A node that uses "videlalvaro/php-amqplib" to communicate with an AMQP server.
 */
final class AmqpNode implements Node
{
    /**
     * Create an AMQP node.
     *
     * @param Connector $connector The connector used to establish AMQP connections.
     *
     * @return AmqpNode
     */
    public static function create(Connector $connector)
    {
        return new self($connector);
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
            $this->connection = $this->connector->connect();
            $this->channel = $this->connection->channel();
            list($this->nodeId) = $this->channel->queue_declare(
                '',    // queue
                false, // passive
                false, // durable
                true   // exclusive
            );
        } catch (AMQPExceptionInterface $e) {
            $this->disconnect();

            throw ConnectionException::create($e);
        }
    }

    /**
     * Disconnect from the AMQP server.
     */
    public function disconnect()
    {
        try {
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (AMQPExceptionInterface $e) {
            // ignore ...
        } finally {
            $this->connection = null;
            $this->channel = null;
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
     * @param Connector $connector The connector used to establish AMQP connections.
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    use IsolatorTrait;

    private $connector;
    private $connection;
    private $channel;
    private $nodeId;
}
