<?php
namespace Skewd\Amqp;

/**
 * A message consumer.
 *
 * A consumer is delivered messages from a queue. Message delivery is
 * load-balanced across the consumers of a queue.
 */
interface Consumer
{
    /**
     * Get the queue from which messages are consumed.
     *
     * @return Queue
     */
    public function queue();

    /**
     * Get the consumer parameters.
     *
     * @return array<ConsumerParameter> The consumer parameters.
     */
    public function parameters();

    /**
     * Get the consumer tag.
     *
     * @return string The consumer tag.
     */
    public function tag();

    /**
     * Pop a message from the message queue.
     *
     * @return Message|null The next message in the queue, or null if there are no messages waiting for this consumer.
     */
    public function pop();

    /**
     * Acknowledge a message.
     *
     * @param Message $message
     */
    public function ack(Message $message);

    /**
     * Reject a message.
     *
     * @param Message $message
     * @param boolean $requeue True to place the message back on the queue; otherwise, false.
     */
    public function reject(Message $message, $requeue = true);

    /**
     * Stop consuming messages.
     */
    public function cancel();
}
