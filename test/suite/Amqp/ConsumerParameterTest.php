<?php
namespace Skewd\Amqp;

use PHPUnit_Framework_TestCase;

class ConsumerParameterTest extends PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $result = ConsumerParameter::normalize([
            ConsumerParameter::NO_ACK(),
        ]);

        $this->assertFalse($result[ConsumerParameter::NO_LOCAL()]);
        $this->assertTrue($result[ConsumerParameter::NO_ACK()]);
        $this->assertFalse($result[ConsumerParameter::EXCLUSIVE()]);
    }

    public function testNormalizeDefaults()
    {
        $result = ConsumerParameter::normalize(null);

        $this->assertFalse($result[ConsumerParameter::NO_LOCAL()]);
        $this->assertFalse($result[ConsumerParameter::NO_ACK()]);
        $this->assertFalse($result[ConsumerParameter::EXCLUSIVE()]);
    }

    public function testNormalizeWithInvalidType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Parameters must be instances of ' . ConsumerParameter::class
        );

        ConsumerParameter::normalize(['<not-a-parameter>']);
    }
}
