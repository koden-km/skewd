<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\Consumer;
use Skewd\Amqp\Queue;
use SplQueue;

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
    public function __construct(
        array $parameters = null,
        $tag = null,
        Queue $queue,
        AMQPChannel $channel
    ) {
        if (null === $parameters) {
            $parameters = ConsumerParameter::defaults();
        } else {
            array_map(
                function (ConsumerParameter $x) {},
                $parameters
            );
        }

        $this->parameters = $parameters;
        $this->tag = $tag;
        $this->queue = $queue;
        $this->channel = $channel;
        $this->message = new SplQueue();
    }

    /**
     * Get the consumer parameters.
     *
     * @return array<ConsumerParameter> The consumer parameters.
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
     * Get the queue from which messages are consumed.
     *
     * @return Queue
     */
    public function queue()
    {
        return $this->queue;
    }

    /**
     * Pop a message from the incoming message queue.
     *
     * @return Message|null The next message in the queue, or null if there are no messages waiting.
     */
    public function pop()
    {
        if ($this->messages->isEmpty()) {
            return null;
        }

        return $this->messages->dequeue();
    }

    /**
     * Push a message onto the incoming message queue.
     *
     * @param Message $message The message.
     */
    public function push(Message $message)
    {
        $this->messages->enqueue($message);
    }

    /**
     * Acknowledge a message.
     *
     * @param Message $message
     */
    public function ack(Message $message)
    {
        $this->channel->basic_ack(
            $message->headers()->get('delivery_tag')
        );
    }

    /**
     * Reject a message.
     *
     * @param Message $message
     * @param boolean $requeue True to place the message back on the queue; otherwise, false.
     */
    public function reject(Message $message, $requeue = true)
    {
        $this->channel->basic_reject(
            $message->headers()->get('delivery_tag'),
            $requeue
        );
    }

    private $parameters;
    private $tag;
    private $queue;
    private $channel;
    private $messages;
}
