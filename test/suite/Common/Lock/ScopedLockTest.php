<?php
namespace Skewd\Common\Lock;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit_Framework_TestCase;

class ScopedLockTest extends PHPUnit_Framework_TestCase
{
    public function testInvokesCallbackUponDestruction()
    {
        $callback = Phony::stub();

        $lock = ScopedLock::create($callback);

        $callback->never()->called();

        $lock = null;

        $callback->called();
    }
}
