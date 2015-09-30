<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\Exchange;
use Skewd\Amqp\ExchangeParameter;
use Skewd\Amqp\ExchangeType;

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
        array $parameters = null,
        AMQPChannel $channel
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->parameters = ExchangeParameter::adapt($parameters);
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
     * @return array<ExchangeParameter> The exchange parameters.
     */
    public function parameters()
    {
        return $this->parameters;
    }

    private $name;
    private $type;
    private $parameters;
    private $channel;
}
