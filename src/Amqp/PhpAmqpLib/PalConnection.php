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

    public function __destruct()
    {
        $this->close();
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

        // Dispatch before calling select in case there's already actions queued
        // in the channels' 'method queue' ...
        if ($this->dispatch()) {
            return ConnectionWaitResult::NORMAL();
        }

        // Otherwise, wait for activity ...
        $ready = @$this->connection->select(
            0,
            intval($timeout * 1000000)
        );

        if (0 === $ready) {
            return ConnectionWaitResult::TIMEOUT();
        } elseif (false === $ready) {
            return ConnectionWaitResult::SIGNAL();
        }

        // Dispatch again if there was channel activity ...
        $this->dispatch();

        return ConnectionWaitResult::NORMAL();
    }

    /**
     * Iteration through all open channels and call wait() so that their method
     * queues / callbacks are dispatched.
     *
     * @return boolean True if any of the channels had activity.
     */
    private function dispatch()
    {
        $activity = false;

        foreach ($this->connection->channels as $channel) {
            try {
                $channel->wait(
                    null, // allowed methods
                    true, // non-blocking
                    1e-7  // timeout (must be non-zero, see below)
                );

                $activity = true;
            } catch (AMQPTimeoutException $e) {
                // PhpAmqpLib treats a timeout of zero specially. It bypasses
                // the stream_select() call, but then calls read() anyway,
                // blocking until more data is available.
                //
                // By using a very low timeout value, we bypass the special
                // handling of zero, but still call select() with a zero timeout
                // so instead of the blocking read() call, we get an
                // AMQPTimeoutException, which we promptly ignore :)
            }
        }

        return $activity;
    }

    private $connection;
}
