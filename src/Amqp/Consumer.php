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
     * Stop consuming messages.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function cancel();
}
