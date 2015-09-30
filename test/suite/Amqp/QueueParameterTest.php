<?php
namespace Skewd\Amqp;

use PHPUnit_Framework_TestCase;

class QueueParameterTest extends PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $result = QueueParameter::normalize([
            QueueParameter::DURABLE(),
            QueueParameter::EXCLUSIVE(),
        ]);

        $this->assertFalse($result[QueueParameter::PASSIVE()]);
        $this->assertTrue($result[QueueParameter::DURABLE()]);
        $this->assertTrue($result[QueueParameter::EXCLUSIVE()]);
        $this->assertFalse($result[QueueParameter::AUTO_DELETE()]);
    }

    public function testNormalizeDefaults()
    {
        $result = QueueParameter::normalize(null);

        $this->assertFalse($result[QueueParameter::PASSIVE()]);
        $this->assertFalse($result[QueueParameter::DURABLE()]);
        $this->assertTrue($result[QueueParameter::EXCLUSIVE()]);
        $this->assertTrue($result[QueueParameter::AUTO_DELETE()]);
    }

    public function testNormalizeWithInvalidType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Parameters must be instances of ' . QueueParameter::class
        );

        QueueParameter::normalize(['<not-a-parameter>']);
    }
}
