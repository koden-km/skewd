<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Skewd\Amqp\Message;

class MessageMarshallerTraitTest extends PHPUnit_Framework_TestCase
{
    // Use the trait to access its private methods ...
    use MessageMarshallerTrait;

    public function setUp()
    {
        $this->amqpMessage = new AMQPMessage(
            '<payload>',
            [
                'reply_to' => '<reply-to>',
                'correlation_id' => '<correlation-id>',
                'application_headers' => [
                    'custom-1' => 'c1',
                    'custom-2' => 'c2',
                ],
            ]
        );

        $this->emptyAmqpMessage = new AMQPMessage();

        $this->standardMessage = Message::create(
            '<payload>',
            [
                'reply_to' => '<reply-to>',
                'correlation_id' => '<correlation-id>',
            ],
            [
                'custom-1' => 'c1',
                'custom-2' => 'c2',
            ]
        );

        $this->emptyStandardMessage = Message::create('');
    }

    public function testToStandardMessage()
    {
        $this->assertEquals(
            $this->standardMessage,
            $this->toStandardMessage($this->amqpMessage)
        );

        $this->assertEquals(
            $this->emptyStandardMessage,
            $this->toStandardMessage($this->emptyAmqpMessage)
        );
    }

    public function testToStandardMessageWhenHeadersAreAmqpTable()
    {
        $message = new AMQPMessage(
            '<payload>',
            [
                'reply_to' => '<reply-to>',
                'correlation_id' => '<correlation-id>',
                'application_headers' => new AMQPTable([
                    'custom-1' => 'c1',
                    'custom-2' => 'c2',
                ]),
            ]
        );

        $this->assertEquals(
            $this->standardMessage,
            $this->toStandardMessage($message)
        );
    }

    public function testFromStandardMessage()
    {
        $this->assertEquals(
            $this->amqpMessage,
            $this->fromStandardMessage($this->standardMessage)
        );

        $this->assertEquals(
            $this->emptyAmqpMessage,
            $this->fromStandardMessage($this->emptyStandardMessage)
        );
    }
}
