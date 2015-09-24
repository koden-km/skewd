<?php
namespace Skewd\Common\Messaging;

use Skewd\Common\Collection\AttributeCollection;

/**
 * A unit of information that can be sent to other nodes.
 *
 * The message consists of a body and a set of properties.
 * Message properties can be used to filter received messages.
 */
final class Message
{
    /**
     * @param string                   $senderId   The ID of the node that is sending the message.
     * @param mixed                    $payload    The main message data.
     * @param AttributeCollection|null $properties Additional message properties.
     *
     * @return Message
     */
    public static function create(
        $senderId,
        $payload,
        AttributeCollection $properties = null
    ) {
        return new self($senderId, $payload, $properties);
    }

    /**
     * Get The ID of the node that is sending the message.
     *
     * @return string The ID of the node that is sending the message.
     */
    public function senderId()
    {
        return $this->senderId;
    }

    /**
     * Get the message's payload data.
     *
     * @return mixed The main message data.
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * Get the message's properties.
     *
     * @return AttributeCollection Additional message properties.
     */
    public function properties()
    {
        return $this->properties;
    }

    /**
     * @param string                   $senderId   The ID of the node that is sending the message.
     * @param mixed                    $payload    The main message data.
     * @param AttributeCollection|null $properties Additional message properties.
     */
    private function __construct(
        $senderId,
        $payload,
        AttributeCollection $properties = null
    ) {
        $this->senderId = $senderId;
        $this->payload = $payload;
        $this->properties = $properties ?: AttributeCollection::create();
    }

    private $senderId;
    private $payload;
    private $properties;
}
