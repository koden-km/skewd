<?php
namespace Skewd\Amqp\PhpAmqpLib;

use Exception;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Skewd\Amqp\Connection\Connection;
use Skewd\Amqp\Connection\ConnectionException;
use Skewd\Amqp\Connection\ConnectionWaitResult;

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
        if (!$this->connection) {
            return;
        }

        try {
            $this->connection->close();
        } finally {
            $this->connection = null;
        }
    }

    /**
     * Create a new AMQP channel.
     *
     * @return Channel The newly created channel.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function channel()
    {
        if (!$this->connection) {
            throw ConnectionException::notConnected();
        }

        try {
            $channel = $this->connection->channel();
        } catch (Exception $e) {
            if ($this->connection->isConnected()) {
                throw $e;
            }

            $this->connection = null;

            throw ConnectionException::notConnected($e);
        }

        return new PalChannel($channel);
    }

    /**
     * Wait for connection activity.
     *
     * @param integer|float $timeout How long to wait for activity, in seconds.
     *
     * @return ConnectionWaitResult
     * @throws ConnectionException  if not connected to the AMQP server.
     */
    public function wait($timeout)
    {
        if (!$this->connection) {
            throw ConnectionException::notConnected();
        }

        $ready = @$this->connection->select(
            0,
            intval($timeout * 1000000)
        );

        if (0 === $ready) {
            return ConnectionWaitResult::TIMEOUT();
        } elseif (false === $ready) {
            return ConnectionWaitResult::SIGNAL();
        }

        foreach ($this->connection->channels as $channel) {
            try {
                $channel->wait(
                    null, // allowed methods
                    true, // non-blocking
                    1e-7  // timeout (must be non-zero, see below)
                );
            } catch (AMQPTimeoutException $e) {
                // PhpAmqpLib treats a timeout of zero specially. It bypasses
                // the stream_select() call, but then calls read() anyway,
                // blocking until more data is available.
                //
                // By using a very low timeout value, we bypass the special
                // handling of zero, so instead of the blocking read() call, we
                // get an AMQPTimeoutException, which we promptly ignore :)
            }
        }

        return ConnectionWaitResult::READY();
    }

    private $connection;
}
