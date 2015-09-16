<?php
namespace Skewd\Application;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Skewd\Process\Process;

class ModularApplicationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->logger = Phony::fullMock(LoggerInterface::class);

        $this->subject = new ModularApplication(
            $this->logger->mock()
        );

        $this->process = Phony::fullMock(Process::class);

        $this->module1 = Phony::fullMock(Module::class);
        $this->module1->name->returns('<module 1>');

        $this->module2 = Phony::fullMock(Module::class);
        $this->module2->name->returns('<module 2>');

        $this->module3 = Phony::fullMock(Module::class);
        $this->module3->name->returns('<module 3>');
    }

    public function testAdd()
    {
        $this->subject->add($this->module1->mock());

        $this->assertTrue(
            $this->subject->has($this->module1->mock())
        );
    }

    public function testRemove()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->remove($this->module1->mock());

        $this->assertFalse(
            $this->subject->has($this->module1->mock())
        );
    }

    public function testHas()
    {
        $this->assertFalse(
            $this->subject->has($this->module1->mock())
        );

        $this->assertFalse(
            $this->subject->has($this->module2->mock())
        );

        $this->subject->add($this->module1->mock());

        $this->assertTrue(
            $this->subject->has($this->module1->mock())
        );

        $this->assertFalse(
            $this->subject->has($this->module2->mock())
        );
    }

    public function testClear()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $this->subject->clear();

        $this->assertFalse(
            $this->subject->has($this->module1->mock())
        );

        $this->assertFalse(
            $this->subject->has($this->module2->mock())
        );
    }

    public function testInitialize()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $result = $this->subject->initialize($this->process->mock());

        $this->module1->initialize->calledWith($this->subject);
        $this->module2->initialize->calledWith($this->subject);

        $this->assertTrue($result);
    }

    public function testInitializeWithFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->initialize->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->initialize($this->process->mock());

        $this->module1->initialize->calledWith($this->subject);
        $this->module2->initialize->calledWith($this->subject);
        $this->module3->initialize->never()->called();

        $this->logger->critical->calledWith(
            'Failed to initialize module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testShutdown()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $result = $this->subject->shutdown();

        $this->module1->shutdown->called();
        $this->module2->shutdown->called();

        $this->assertTrue($result);
    }

    public function testShutdownWithFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->shutdown->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->shutdown();

        $this->module1->shutdown->called();
        $this->module2->shutdown->called();
        $this->module3->shutdown->called();

        $this->logger->warning->calledWith(
            'Failed to shut down module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testTick()
    {
        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());

        $result = $this->subject->tick();

        $this->module1->tick->called();
        $this->module2->tick->called();

        $this->assertTrue($result);
    }

    public function testTickWithFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->tick->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->tick();

        $this->module1->tick->called();
        $this->module2->tick->called();
        $this->module3->tick->never()->called();

        $this->logger->critical->calledWith(
            'Failure in module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }
}
