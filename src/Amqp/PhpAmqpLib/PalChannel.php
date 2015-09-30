<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Skewd\Amqp\Channel;
use Skewd\Amqp\ExchangeParameter;
use Skewd\Amqp\ExchangeType;
use Skewd\Amqp\QueueParameter;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * An AMQP channel.
 */
final class PalChannel implements Channel
{
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Declare an exchange.
     *
     * @param string                        $name       The exchange name.
     * @param ExchangeType                  $type       The exchange type.
     * @param array<ExchangeParameter>|null $parameters Parameters to set on the exchange, or null to use the defaults.
     *
     * @return Exchange         The exchange.
     * @throws DeclareException if the exchange could not be declared because it already exists with different parameters.
     */
    public function exchange($name, ExchangeType $type, array $parameters = null)
    {
        $parameters = ExchangeParameters::adapt($parameters);

        try {
            $this->channel->exchange_declare(
                $name,
                $type->value(),
                in_array(ExchangeParameter::PASSIVE(), $parameters, true),
                in_array(ExchangeParameter::DURABLE(), $parameters, true),
                in_array(ExchangeParameter::AUTO_DELETE(), $parameters, true),
                in_array(ExchangeParameter::INTERNAL(), $parameters, true)
            );
        } catch (AMQPProtocolChannelException $e) {
            throw DeclareException::exchangeParameterMismatch(
                $name,
                $parameters,
                $e
            );

            throw $e;
        }

        return new PhpAmqpLibExchange(
            $name,
            $type,
            $parameters,
            $this->channel
        );
    }

    /**
     * Get the built-in nameless direct exchange.
     *
     * Every queue that is created it automatically bound to the default
     * exchange with a routing key the same as the queue name.
     *
     * @return Exchange The exchange.
     */
    public function defaultExchange()
    {
        return new PhpAmqpLibExchange(
            '',
            ExchangeType::DIRECT(),
            [ExchangeParameter::DURABLE()],
            $this->channel
        );
    }

    /**
     * Get the built-in exchange of the given type.
     *
     * @param ExchangeType $type The exchange type.
     *
     * @return Exchange The exchange.
     */
    public function builtInExchange(ExchangeType $type)
    {
        return new PhpAmqpLibExchange(
            'amq.' . $type->value(),
            $type,
            [ExchangeParameter::DURABLE()],
            $this->channel
        );
    }

    /**
     * Declare a queue.
     *
     * @param string                     $name       The queue name, or an empty string to have the server generated a name.
     * @param array<QueueParameter>|null $parameters Parameters to set on the queue, or null to use the defaults.
     *
     * @return Queue            The queue.
     * @throws DeclareException if the queue could not be declared because it already exists with different parameters.
     */
    public function queue($name = '', array $parameters = null)
    {
        $parameters = QueueParameters::adapt($parameters);

        try {
            list($name) = $this->channel->queue_declare(
                $name,
                in_array(QueueParameter::PASSIVE(), $parameters, true),
                in_array(QueueParameter::DURABLE(), $parameters, true),
                in_array(QueueParameter::EXCLUSIVE(), $parameters, true),
                in_array(QueueParameter::AUTO_DELETE(), $parameters, true)
            );
        } catch (AMQPProtocolChannelException $e) {
            if (self::AMQP_RESOURCE_LOCKED_CODE === $e->getCode()) {
                throw DeclareException::queueAlreadyDeclared(
                    $this->name,
                    $e
                );
            }

            throw DeclareException::queueParameterMismatch(
                $this->name,
                $this->parameters,
                $e
            );
        }

        return new PhpAmqpLibQueue(
            $name,
            $parameters,
            $this->channel
        );
    }

    /**
     * Close the channel.
     */
    public function close()
    {
        try {
            $this->channel->close();
        } catch (HANDLE_DISCONNECT $e) {
        } catch (AMQPExceptionInterface $e) {
            // ignore ...
        } finally {
            $this->channel = null;
        }
    }

    const AMQP_RESOURCE_LOCKED_CODE = 405;

    private $channel;
}
