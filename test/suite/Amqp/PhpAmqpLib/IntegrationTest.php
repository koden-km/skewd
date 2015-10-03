<?php
namespace Skewd\Amqp\PhpAmqpLib;

use PHPUnit_Framework_TestCase;
use Skewd\Amqp\IntegrationTestTrait;

/**
 * @group integration
 */
class IntegrationTest extends PHPUnit_Framework_TestCase
{
    use IntegrationTestTrait;

    public function createConnector()
    {
        return PalConnector::create();
    }
}
