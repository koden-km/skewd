<?php
namespace Skewd\Amqp\PhpAmqpLib;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use Skewd\Amqp\Exchange;
use Skewd\Amqp\ExchangeParameter;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\Message;
use Skewd\Amqp\PublishOption;
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
     * @param Message                   $message    The message to publish.
     * @param string                    $routingKey The routing key for DIRECT and TOPIC exchanges, or empty string for FANOUT and HEADERS exchanges.
     * @param array<PublishOption>|null $options    An array of options to set, or null to use the defaults.
     *
     * @throws ConnectionException      if not connected to the AMQP server.
     * @throws InvalidArgumentException if a routing key is required but not provided, and vice-versa.
     */
    public function publish(
        Message $message,
        $routingKey = '',
        array $options = null
    ) {
        if ($this->type->requiresRoutingKey()) {
            if ('' === $routingKey) {
                throw new InvalidArgumentException(
                    'Routing key must be provided for ' . $this->type->key() . ' exchanges.'
                );
            }
        } elseif ('' !== $routingKey) {
            throw new InvalidArgumentException(
                'Routing key must be empty for ' . $this->type->key() . ' exchanges.'
            );
        }

        $options = PublishOption::normalize($options);

        $this->channel->basic_publish(
            $this->fromStandardMessage($message),
            $this->name,
            $routingKey,
            $options[PublishOption::MANDATORY()],
            $options[PublishOption::IMMEDIATE()]
        );
    }

    use MessageMarshallerTrait;

    private $name;
    private $type;
    private $parameters;
    private $channel;
}
