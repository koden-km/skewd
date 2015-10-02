<?php
namespace Skewd\Amqp;

use PHPUnit_Framework_TestCase;

class PublishOptionTest extends PHPUnit_Framework_TestCase
{
    public function testNormalize()
    {
        $result = PublishOption::normalize([
            PublishOption::MANDATORY(),
        ]);

        $this->assertTrue($result[PublishOption::MANDATORY()]);
        $this->assertFalse($result[PublishOption::IMMEDIATE()]);
    }

    public function testNormalizeDefaults()
    {
        $result = PublishOption::normalize(null);

        $this->assertFalse($result[PublishOption::MANDATORY()]);
        $this->assertFalse($result[PublishOption::IMMEDIATE()]);
    }

    public function testNormalizeWithInvalidType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Options must be instances of ' . PublishOption::class
        );

        PublishOption::normalize(['<not-a-parameter>']);
    }
}
