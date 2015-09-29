<?php
namespace Skewd\Amqp;

use Skewd\Common\Collection\AttributeCollection;

/**
 * An AMQP message.
 */
final class Message
{
    /**
     * Create a message.
     *
     * @param string                   $payload          The main message payload.
     * @param AttributeCollection|null $amqpProperties   AMQP message properties.
     * @param AttributeCollection|null $customProperties Application-specific message properties.
     *
     * @return Message
     */
    public static function create(
        $payload,
        AttributeCollection $amqpProperties = null,
        AttributeCollection $customProperties = null,
    ) {
        return new self(
            $payload,
            $amqpProperties,
            $customProperties
        );
    }

    /**
     * Get the message payload.
     *
     * @return string The message payload.
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * Get the message's AMQP properties.
     *
     * @return AttributeCollection AMQP message properties.
     */
    public function amqpProperties()
    {
        return $this->amqpProperties;
    }

    /**
     * Get the message's application-specific properties.
     *
     * @return AttributeCollection Application-specific message properties.
     */
    public function customerProperties()
    {
        return $this->customerProperties;
    }

    /**
     * @param string                   $payload          The main message payload.
     * @param AttributeCollection|null $amqpProperties   AMQP message properties.
     * @param AttributeCollection|null $customProperties Application-specific message properties.
     */
    private function __construct(
        $payload,
        AttributeCollection $amqpProperties = null,
        AttributeCollection $customProperties = null,
    ) {
        $this->payload = $payload;
        $this->amqpProperties = $amqpProperties ?: AttributeCollection::create();
        $this->customerProperties = $customerProperties ?: AttributeCollection::create();
    }

    private $payload;
    private $amqpProperties;
    private $customProperties;
}
