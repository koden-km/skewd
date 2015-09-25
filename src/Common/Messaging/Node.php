<?php
namespace Skewd\Common\Messaging;

/**
 * A node in a Skewd network.
 */
interface Node
{
    /**
     * Get this node's unique ID.
     *
     * The node ID is only available when connected to an AMQP server.
     *
     * @return string|null The node ID if connected; otherwise, null.
     */
    public function id();

    /**
     * Connect to the AMQP server.
     *
     * If a connection has already been established it is first disconnected.
     *
     * @throws ConnectionException The connection could not be established.
     */
    public function connect();

    /**
     * Disconnect from the AMQP server.
     *
     * Disconnecting from the server invalidates all AMQP channels created for
     * this node.
     */
    public function disconnect();

    /**
     * Check if there is currently a connection to the AMQP server.
     *
     * @return boolean True if currently connected; otherwise, false.
     */
    public function isConnected();

    /**
     * Create an AMQP channel.
     *
     * @return Channel The newly created channel.
     */
    public function createChannel();
}
