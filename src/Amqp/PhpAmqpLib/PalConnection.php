<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Skewd\Amqp\Connection\Connection;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * A connection to an AMQP server.
 */
final class PalConnection implements Connection
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
        if (!$this->connection) {
            return false;
        } elseif ($this->connection->isConnected()) {
            return true;
        }

        $this->connection = null;

        return false;
    }

    /**
     * Disconnect from the server.
     *
     * No action is taken if already disconnected.
     */
    public function close()
    {
        if ($this->connection) {
            $connection = $this->connection;
            $this->connection = null;
            $connection->close();
        }
    }

    /**
     * Create a new AMQP channel.
     *
     * @return Channel The newly created channel.
     *
     * @throws ChannelException    if the channel can not be created.
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function channel()
    {
        try {
            $channel = $this->connection->channel();
        } catch (CHANNEL_EXCEPTION $e) {
            throw ChannelException::creationFailure($e);
        } catch (CONNECTION_EXCEPTION $e) {
            // LOOKS like this is a AMQPRuntimeException, but that may not be enough to tell :/
            $this->connection = null;

            throw ConnectionException::notConnected(e);
        }

        return new PalChannel($channel);
    }

    /**
     * Wait for connection activity.
     *
     * @return ConnectionWaitResult
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function wait($timeout)
    {
        throw new \LogicException('Not implemented.');
    }

    private $connection;
}
