<?php
namespace Skewd\Common\Messaging;

/**
 * A unit of information that can be sent to other nodes.
 *
 * The message consists of a body and a set of properties.
 * Message properties can be used to filter received messages.
 */
final class Message
{
    /**
     * @param mixed                $payload    The main message data.
     * @param array<string,string> $properties Additional message properties.
     *
     * @return Message
     */
    public static function create($payload, array $properties = [])
    {
        return new self($payload, $properties);
    }

    /**
     * Get the node ID of the sender.
     *
     * @return string The node ID of the sender.
     */
    public function nodeId()
    {
        return $this->nodeId;
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
     * @return array<string,string> Additional message properties.
     */
    public function properties()
    {
        return $this->properties;
    }

    /**
     * @param string               $nodeId     The node ID of the sender.
     * @param mixed                $payload    The main message data.
     * @param array<string,string> $properties Additional message properties.
     */
    private function __construct($nodeId, $payload, array $properties = [])
    {
        $this->nodeId = $nodeId;
        $this->payload = $payload;
        $this->properties = $properties;
    }

    private $nodeId;
    private $payload;
    private $properties;
}
