<?php
namespace Skewd\Amqp;

use PHPUnit_Framework_TestCase;

class ExchangeParameterTest extends PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $result = ExchangeParameter::normalize([
            ExchangeParameter::DURABLE(),
            ExchangeParameter::INTERNAL(),
        ]);

        $this->assertFalse($result[ExchangeParameter::PASSIVE()]);
        $this->assertTrue($result[ExchangeParameter::DURABLE()]);
        $this->assertFalse($result[ExchangeParameter::AUTO_DELETE()]);
        $this->assertTrue($result[ExchangeParameter::INTERNAL()]);
    }

    public function testNormalizeDefaults()
    {
        $result = ExchangeParameter::normalize(null);

        $this->assertFalse($result[ExchangeParameter::PASSIVE()]);
        $this->assertFalse($result[ExchangeParameter::DURABLE()]);
        $this->assertTrue($result[ExchangeParameter::AUTO_DELETE()]);
        $this->assertFalse($result[ExchangeParameter::INTERNAL()]);
    }

    public function testNormalizeWithInvalidType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Parameters must be instances of ' . ExchangeParameter::class
        );

        ExchangeParameter::normalize(['<not-a-parameter>']);
    }
}
