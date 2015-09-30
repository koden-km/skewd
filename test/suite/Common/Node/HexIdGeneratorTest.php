<?php
namespace Skewd\Common\Node;

use Eloquent\Phony\Phpunit\Phony;
use Icecave\Isolator\Isolator;
use PHPUnit_Framework_TestCase;

class HexIdGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->isolator = Phony::fullMock(Isolator::class);

        $this
            ->isolator
            ->mt_rand
            ->with(0, 0xff)
            ->returns(
                1, 2,
                3, 4,
                5, 6,
                7, 8,
                9, 10
            );

        $this->subject = HexIdGenerator::create();

        $this->subject->setIsolator($this->isolator->mock());
    }

    public function testGenerate()
    {
        $this->assertSame(
            [
                '0102',
                '0304',
                '0506',
                '0708',
                '090a',
            ],
            $this->subject->generate(5)
        );
    }

    public function testGenerateDoesNotIncludeDuplicates()
    {
        $this
            ->isolator
            ->mt_rand
            ->with(0, 0xff)
            ->returns(
                1, 2,
                1, 2,
                3, 4,
                5, 6,
                5, 6,
                7, 8,
                9, 10
            );

        $this->assertSame(
            [
                '0102',
                '0304',
                '0506',
                '0708',
                '090a',
            ],
            $this->subject->generate(5)
        );
    }
}
