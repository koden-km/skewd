<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Amqp\Connection;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * A connection to an AMQP server.
 */
final class PhpAmqpLibConnection implements Connection
{
    public function __construct(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Check if the connection is still established.
     *
     * @return boolean True if connected; otherwise, false.
     */
    public function isConnected()
    {
        if ($this->connection) {
            return $this->connection->isConnected();
        }

        return false;
    }

    /**
     * Disconnect from the server.
     *
     * No action is taken if already disconnected.
     */
    public function close()
    {
        try {
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (AMQPExceptionInterface $e) {
            // ignore ...
        } finally {
            $this->connection = null;
        }
    }

    /**
     * Create a new AMQP channel.
     *
     * @return Channel The newly created channel.
     *
     * @throws ConnectionException if the connection has not been established.
     * @throws ChannelException    if the channel can not be created.
     */
    public function channel()
    {
        return new PhpAmqpLibChannel(
            $this->connection->channel()
        );
    }

    /**
     * Wait for connection activity.
     *
     * @return boolean True if the wait operation was interrupted by a signal; otherwise, false.
     */
    public function wait($timeout)
    {
        throw new \LogicException('Not implemented.');
    }

    private $connection;
}
