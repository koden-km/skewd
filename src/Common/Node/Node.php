<?php
namespace Skewd\Common\Node;

use Skewd\Common\Lock\LockManager;

/**
 * A node on the network.
 *
 * Any AMQP connection that participates in a Skewd network is considered a
 * node.
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

    /**
     * Get the node's lock manager.
     *
     * @return LockManager The lock manager.
     */
    public function lockManager();

    /**
     * Wait for network activity.
     *
     * @param integer|float $timeout The number of seconds to wait for technology.
     *
     * @return boolean True if the operation was interrupted by a signal; otherwise, false.
     */
    public function wait($timeout);
}
