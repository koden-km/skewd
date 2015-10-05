<?php
namespace Skewd\Lock;

use Eloquent\Phony\Phpunit\Phony;
use LogicException;
use PHPUnit_Framework_TestCase;

class LockTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->releaseCallback = Phony::stub();

        $this->subject = Lock::create(
            LockMode::EXCLUSIVE(),
            $this->releaseCallback
        );
    }

    public function testReleaseOnDestruct()
    {
        $this->releaseCallback->never()->called();

        $this->subject = null;

        $this->releaseCallback->called();
    }

    public function testDoesNotReleaseOnDestructIfReleased()
    {
        $this->subject->release();
        $this->subject = null;

        $this->releaseCallback->once()->called();
    }

    public function testDoesNotReleaseOnDestructIfDetached()
    {
        $this->subject->detach();
        $this->subject = null;

        $this->releaseCallback->never()->called();
    }

    public function testMode()
    {
        $this->assertSame(
            LockMode::EXCLUSIVE(),
            $this->subject->mode()
        );
    }

    public function testRelease()
    {
        $this->subject->release();

        $this->releaseCallback->called();
    }

    public function testReleaseWhenReleased()
    {
        $this->subject->release();

        $this->setExpectedException(
            LogicException::class,
            'Can not release lock, it has already been released.'
        );

        try {
            $this->subject->release();
        } catch (LogicException $e) {
            $this->releaseCallback->once()->called();

            throw $e;
        }
    }

    public function testReleaseWhenDetached()
    {
        $this->subject->detach();

        $this->setExpectedException(
            LogicException::class,
            'Can not release lock, it has been detached.'
        );

        try {
            $this->subject->release();
        } catch (LogicException $e) {
            $this->releaseCallback->never()->called();

            throw $e;
        }
    }

    public function testDetach()
    {
        $releaseCallback = $this->subject->detach();

        $this->releaseCallback->never()->called();

        $releaseCallback();

        $this->releaseCallback->called();

        $this->assertSame(
            $this->releaseCallback,
            $releaseCallback
        );
    }

    public function testDetachWhenDetached()
    {
        $this->subject->detach();

        $this->setExpectedException(
            LogicException::class,
            'Can not detach lock, it has already been detached.'
        );

        try {
            $this->subject->detach();
        } catch (LogicException $e) {
            $this->releaseCallback->never()->called();

            throw $e;
        }
    }

    public function testDetachWhenReleased()
    {
        $this->subject->release();

        $this->setExpectedException(
            LogicException::class,
            'Can not detach lock, it has been released.'
        );

        try {
            $this->subject->detach();
        } catch (LogicException $e) {
            $this->releaseCallback->once()->called();

            throw $e;
        }
    }
}
