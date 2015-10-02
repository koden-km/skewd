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
     * @return SplObjectStorage<ConsumerParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters();

    /**
     * Get the consumer tag.
     *
     * @return string The consumer tag.
     */
    public function tag();

    /**
     * Acknowledge a message.
     *
     * @param Message $message
     *
     * @throws ConnectionException if not connected to the AMQP server.
     * @throws LogicException      if the message was not delivered via this consumer.
     * @throws LogicException      if this consumer is using ConsumerParameter::NO_ACK.
     */
    public function ack(Message $message);

    /**
     * Reject a message.
     *
     * @param Message $message
     * @param boolean $requeue True to place the message back on the queue; otherwise, false.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function reject(Message $message, $requeue = true);

    /**
     * Stop consuming messages.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function cancel();
}
