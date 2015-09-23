<?php
namespace Skewd\Common\Messaging;

/**
 * An interface for sending messages.
 */
interface Publisher
{
    /**
     * Enqueue a message to be sent directly to a single node.
     *
     * @param string $nodeId The recipient node ID.
     * @param Message $message The message to send.
     */
    public function sendToNode($nodeId, Message $message);

    /**
     * Enqueue a message to be sent to a group of nodes.
     *
     * @param string  $group   The group of recipient nodes.
     * @param Message $message The message to send.
     */
    public function sendToGroup($group, Message $message);

    /**
     * Enqueue a message to be sent to one of the members of a group.
     *
     * @param string  $group   The group of recipient nodes.
     * @param Message $message The message to send.
     */
    public function sendToAnyGroupMember($group, Message $message);

    /**
     * Enqueue a message to be sent to all nodes.
     *
     * @param Message $message The message to send.
     */
    public function sendToAll(Message $message);
}
