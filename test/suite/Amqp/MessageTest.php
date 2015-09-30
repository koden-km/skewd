<?php
namespace Skewd\Amqp;

use PHPUnit_Framework_TestCase;
use Skewd\Common\Collection\AttributeCollection;

class MessageTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->amqpProperties = AttributeCollection::create([
            'a' => '1',
            'b' => '2',
        ]);

        $this->customProperties = AttributeCollection::create([
            'c' => '3',
            'd' => '4',
        ]);

        $this->subject = Message::create(
            '<payload>',
            $this->amqpProperties,
            $this->customProperties
        );
    }

    public function testPayload()
    {
        $this->assertSame(
            '<payload>',
            $this->subject->payload()
        );
    }

    public function testAmqpProperties()
    {
        $this->assertSame(
            $this->amqpProperties,
            $this->subject->amqpProperties()
        );
    }

    public function testCustomProperties()
    {
        $this->assertSame(
            $this->customProperties,
            $this->subject->customProperties()
        );
    }
}
