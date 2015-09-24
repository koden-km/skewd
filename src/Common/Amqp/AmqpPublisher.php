<?php
namespace Skewd\Common\Amqp;

/**
 * A message publisher sends messages to other nodes.
 */
final class AmqpPublisher implements Publisher
{
    public function __construct(AMQPChannel $channel, $domain)
    {
        $this->channel = $channel;
        $this->domain = $domain;
        $this->initialized = false;
    }

    /**
     * Send a message directly to another node.
     *
     * @param string $nodeId The recipient node ID.
     * @param Message $message The message to send.
     */
    public function sendToNode($nodeId, Message $message)
    {
        $this->initialize();

        $this->channel->basic_publish(
            $this->marshallMessage($message),
            $this->exchangeName('unicast'),
            $nodeId // routing key
        );
    }

    /**
     * Send a message to a group of nodes.
     *
     * @param string  $group   The group of recipient nodes.
     * @param Message $message The message to send.
     */
    public function sendToGroup($group, Message $message);
    {
        $this->initialize();

        $this->channel->basic_publish(
            $this->marshallMessage(
                $message,
                ['g' => $group]
            ),
            $this->exchangeName('multicast')
        );
    }

    /**
     * Send a message to all nodes.
     *
     * @param Message $message The message to send.
     */
    public function sendToAll(Message $message)
    {
        $this->initialize();

        $this->channel->basic_publish(
            $this->marshallMessage($message),
            $this->exchangeName('broadcast')
        );
    }

    private function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->channel->exchange_declare(
            $this->exchangeName('unicast'),
            'direct',
            false, // passive
            false, // durable
            false  // auto-delete
        );

        $this->channel->exchange_declare(
            $this->exchangeName('multicast'),
            'headers',
            false, // passive
            false, // durable
            false  // auto-delete
        );

        $this->channel->exchange_declare(
            $this->exchangeName('broadcast'),
            'fanout',
            false, // passive
            false, // durable
            false  // auto-delete
        );

        $this->initialized = true;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function exchangeName($type)
    {
        return $this->domain . '/' . $type;
    }

    /**
     * @param Message              $message
     * @param array<string,string> $headers Additional AMQP headers.
     *
     * @return AMQPMessage
     */
    private function marshallMessage(Message $message, array $headers = [])
    {
        $payload = @json_encode($message->payload());

        if ($payload === false) {
            throw new InvalidArgumentException(
                'Message payload could not be encoded.'
            );
        }

        $headers['n'] = $message->nodeId();

        foreach ($message->properties() as $name => $value) {
            $headers['m-' . $name] = $value;
        }

        return new AMQPMessage(
            $payload,
            ['application_headers' => $headers]
        );
    }

    private $channel;
    private $domain;
    private $initialized;
}
