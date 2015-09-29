<?php
namespace Skewd\Amqp\Connection;

/**
 * A connection to an AMQP server.
 */
interface Connection
{
    /**
     * Check if the connection is still established.
     *
     * @return boolean True if connected; otherwise, false.
     */
    public function isConnected();

    /**
     * Disconnect from the server.
     *
     * No action is taken if already disconnected.
     */
    public function close();

    /**
     * Create a new AMQP channel.
     *
     * @return Channel The newly created channel.
     *
     * @throws ChannelException    if the channel can not be created.
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function channel();

    /**
     * Wait for connection activity.
     *
     * @return ConnectionWaitResult
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function wait($timeout);
}
