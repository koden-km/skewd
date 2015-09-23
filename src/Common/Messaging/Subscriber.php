<?php
namespace Skewd\Common\Messaging;

/**
 * An interface for receiving messages.
 */
interface Subscriber
{
    /**
     * Pop a message from the incoming message queue.
     *
     * The message queue contains all messages intended for this node,
     * regardless of whether they were sent via the unicast, multicast or
     * broadcast method.
     *
     * @return Message|null The next message in the queue, or null if the queue is empty.
     */
    public function pop();

    /**
     * Join a multicast group.
     *
     * If the optional filter is provided only messages with properties that
     * match the filter will be received.
     *
     * @param string               $group  The group name.
     * @param array<string,string> $filter The message filter.
     *
     * @return string The group member ID.
     */
    public function join($group, array $filter = null);

    /**
     * Leave a multicast group.
     *
     * @param string $memberId The node's group member ID.
     */
    public function leave($memberId);
}
