<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\Exchange;
use Skewd\Amqp\ExchangeParameter;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\Message;
use SplObjectStorage;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * An AMQP exchange.
 */
final class PalExchange implements Exchange
{
    public function __construct(
        $name,
        ExchangeType $type,
        SplObjectStorage $parameters,
        AMQPChannel $channel
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->parameters = $parameters;
        $this->channel = $channel;
    }

    /**
     * Get the name of the exchange.
     *
     * @return string The exchange name.
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the exchange type.
     *
     * @return ExchangeType The exchange type.
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Get the exchange parameters.
     *
     * @return SplObjectStorage<ExchangeParameter, boolean> A map of parameter to on/off state.
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Publish a message to this exchange.
     *
     * @param Message $message The message to publish.
     */
    public function publish(Message $message)
    {
        throw new \LogicException('Not implemented.');
    }

    private $name;
    private $type;
    private $parameters;
    private $channel;
}
