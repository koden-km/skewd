<?php
namespace Skewd\Amqp;

use Skewd\Collection\AttributeCollection;

/**
 * An AMQP message.
 */
final class Message
{
    /**
     * Create a message.
     *
     * @param string                    $payload          The main message payload.
     * @param AttributeCollection|array $amqpProperties   AMQP message properties.
     * @param AttributeCollection|array $customProperties Application-specific message properties.
     *
     * @return Message
     */
    public static function create(
        $payload,
        $amqpProperties = [],
        $customProperties = []
    ) {
        if (!$amqpProperties instanceof AttributeCollection) {
            $amqpProperties = AttributeCollection::create($amqpProperties);
        }

        if (!$customProperties instanceof AttributeCollection) {
            $customProperties = AttributeCollection::create($customProperties);
        }

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
    public function customProperties()
    {
        return $this->customProperties;
    }

    /**
     * @param string                    $payload          The main message payload.
     * @param AttributeCollection|array $amqpProperties   AMQP message properties.
     * @param AttributeCollection|array $customProperties Application-specific message properties.
     */
    private function __construct(
        $payload,
        AttributeCollection $amqpProperties,
        AttributeCollection $customProperties
    ) {
        $this->payload = $payload;
        $this->amqpProperties = $amqpProperties;
        $this->customProperties = $customProperties;
    }

    private $payload;
    private $amqpProperties;
    private $customProperties;
}
