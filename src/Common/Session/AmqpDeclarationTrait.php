<?php
namespace Skewd\Common\Session;

use PhpAmqpLib\Channel\AMQPChannel;

/**
 * *** THIS TYPE IS NOT PART OF THE PUBLIC API ***
 *
 * @access private
 */
trait AmqpDeclarationTrait
{
    private function multicastExchange(AMQPChannel $channel)
    {
        if (!$this->multicastExchangeName) {
            $exchange = 'session.v1.multicast';

            $channel->exchange_declare(
                $exchange,
                'fanout',
                false, // passive,
                false, // durable,
                false  // auto delete
            );

            $this->multicastExchangeName = $exchange;
        }

        return $this->multicastExchangeName;
    }

    private function unicastExchange(AMQPChannel $channel)
    {
        if (!$this->unicastExchangeName) {
            $exchange = 'session.v1.unicast';

            $channel->exchange_declare(
                $exchange,
                'direct',
                false, // passive,
                false, // durable,
                false  // auto delete
            );

            $this->unicastExchangeName = $exchange;
        }

        return $this->unicastExchangeName;
    }

    private function exclusiveQueue(AMQPChannel $channel, callable $callback)
    {
        list($queue) = $channel->queue_declare(
            '',    // name
            false, // passive
            false, // durable,
            true   // exclusive
        );

        $channel->queue_bind(
            $queue,
            $this->multicastExchange($channel)
        );

        $channel->queue_bind(
            $queue,
            $this->unicastExchange($channel),
            $queue
        );

        $channel->basic_consume(
            $queue,
            '',   // consumer tag
            true, // no local (do not receive own messages)
            true, // no ack
            true, // exclusive
            false, // nowait
            $callback
        );

        return $queue;
    }

    private $multicastExchangeName;
    private $unicastExchangeName;
}
