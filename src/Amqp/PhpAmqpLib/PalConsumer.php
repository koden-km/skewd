<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\ConsumerParameter;
use Skewd\Amqp\Message;
use Skewd\Amqp\Queue;
use SplObjectStorage;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * A message consumer.
 */
final class PalConsumer implements Consumer
{
    /**
     * @param Queue            $queue
     * @param SplObjectStorage $parameters
     * @param string           $tag
     * @param AMQPChannel      $channel
     */
    public function __construct(
        Queue $queue,
        SplObjectStorage $parameters,
        $tag,
        AMQPChannel $channel
    ) {
        $this->queue = $queue;
        $this->parameters = $parameters;
        $this->tag = $tag;
        $this->channel = $channel;
    }

    /**
     * Get the queue from which messages are consumed.
     *
     * @return Queue
     */
    public function queue()
    {
        return $this->queue;
    }

    /**
     * Get the consumer parameters.
     *
     * @return SplObjectStorage<ConsumerParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Get the consumer tag.
     *
     * @return string The consumer tag.
     */
    public function tag()
    {
        return $this->tag;
    }

    /**
     * Set the consumer tag.
     *
     * Note that this method is not part of the Consumer interface, it is
     * present to allow server-generated tags to get set after the consumer is
     * constructed.
     *
     * @param string $tag The consumer tag.
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Stop consuming messages.
     *
     * @throws ConnectionException if not connected to the AMQP server.
     */
    public function cancel()
    {
        $this->channel->basic_cancel($this->tag);
    }

    private $queue;
    private $parameters;
    private $tag;
    private $channel;
}
