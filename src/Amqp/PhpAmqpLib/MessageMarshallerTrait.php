<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Skewd\Amqp\Message;

/**
 * Please note that this code is not part of the public API. It may be changed
 * or removed at any time without notice.
 *
 * @access private
 *
 * Provides methods for converting AMQP between Skewd and PhpAmqpLib formats.
 */
trait MessageMarshallerTrait
{
    /**
     * Convert an AMQP message from PhpAmqpLib format to a standard message.
     *
     * @param AMQPMessage $message
     *
     * @return Message
     */
    private function toStandardMessage(AMQPMessage $message)
    {
        $amqpProperties = $message->get_properties();

        if (isset($amqpProperties['application_headers'])) {
            $customProperties = $amqpProperties['application_headers'];
            unset($amqpProperties['application_headers']);

            if ($customProperties instanceof AMQPTable) {
                $customProperties = $customProperties->getNativeData();
            }
        } else {
            $customProperties = [];
        }

        return Message::create(
            $message->body,
            $amqpProperties,
            $customProperties
        );
    }

    /**
     * Convert an AMQP message to PhpAmqpLib format from a standard message.
     *
     * @param Message $message
     *
     * @return AMQPMessage
     */
    private function fromStandardMessage(Message $message)
    {
        $properties = iterator_to_array(
            $message->amqpProperties()
        );

        if (!$message->customProperties()->isEmpty()) {
            $properties['application_headers'] = iterator_to_array(
                $message->customProperties()
            );
        }

        return new AMQPMessage(
            $message->payload(),
            $properties
        );
    }
}
