<?php
namespace Skewd\Common\Messaging;

use PHPUnit_Framework_TestCase;
use Skewd\Common\Collection\AttributeCollection;

class MessageTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->properties = AttributeCollection::create([
            'a' => '1',
            'b' => '2',
        ]);

        $this->subject = Message::create(
            '<sender-id>',
            '<payload>',
            $this->properties
        );
    }

    public function testNodeId()
    {
        $this->assertSame(
            '<sender-id>',
            $this->subject->senderId()
        );
    }

    public function testPayload()
    {
        $this->assertSame(
            '<payload>',
            $this->subject->payload()
        );
    }

    public function testProperties()
    {
        $this->assertSame(
            $this->properties,
            $this->subject->properties()
        );
    }
}
