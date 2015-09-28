<?php
namespace Skewd\Common\Lock;

use Exception;
use PHPUnit_Framework_TestCase;

class LockExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testAlreadyLocked()
    {
        $previous = new Exception();
        $exception = LockException::alreadyLocked('<resource>', $previous);

        $this->assertSame(
            'Failed to lock resource "<resource>", resource is already locked.',
            $exception->getMessage()
        );

        $this->assertSame(
            $previous,
            $exception->getPrevious()
        );
    }
}
