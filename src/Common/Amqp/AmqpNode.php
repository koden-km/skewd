<?php
namespace Skewd\Common\Amqp;

use Icecave\Isolator\IsolatorTrait;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
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
            $this->nodeId = $this->generateNodeId();
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
     * @param Connector $connector The connector used to establish AMQP connections.
     */
    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Generate a unique ID for this node.
     *
     * @return string
     */
    private function generateNodeId()
    {
        $iso = $this->isolator();
        $previous = [];

        while (true) {
            // Generate a 4-byte random ID ...
            do {
                $id = sprintf(
                    '%04x',
                    $id = $iso->mt_rand(
                        0,
                        $iso->mt_getrandmax() & 0xffff
                    )
                );
            } while (isset($previous[$id]))

            $previous[$id] = true;

            // Create a new AMQP channel ...
            $channel = $this->connection->channel();

            try {
                // Attempt to create an exclusive queue based on the randomly
                // generated unique ID ...
                $channel->queue_declare(
                    'node-' . $id,
                    false, // passive
                    false, // durable
                    true   // exclusive
                );

                return $id;
            } catch (AMQPProtocolChannelException $e) {
                // The error is NOT about the queue (and hence the ID) being
                // unavailable, simply re-throw the exception ...
                if ($e->getCode() !== self::AMQP_RESOURCE_LOCKED_CODE) {
                    throw $e;

                // The error indicates the ID is unavailable, throw an exception
                // if we've exhausted our attempts ...
                } elseif (0 === --$remainingAttempts) {
                    throw ConnectionException::create($e);
                }
            } finally {
                $channel->close();
            }
        }
    }

    const AMQP_RESOURCE_LOCKED_CODE = 405;
    const MAX_ID_GENERATION_ATTEMPTS = 5;

    use IsolatorTrait;

    private $connector;
    private $connection;
    private $nodeId;
}
