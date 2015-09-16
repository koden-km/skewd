<?php
namespace Skewd\Server;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;

class ModuleCollectionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->logger = Phony::mock(LoggerInterface::class);

        $this->subject = new ModuleCollection(
            $this->logger->mock()
        );

        $this->server = Phony::mock(Server::class);

        $this->module1 = Phony::mock(Module::class);
        $this->module1->name->returns('<module 1>');

        $this->module2 = Phony::mock(Module::class);
        $this->module2->name->returns('<module 2>');

        $this->module3 = Phony::mock(Module::class);
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

        $result = $this->subject->initialize($this->server->mock());

        $this->module1->initialize->calledWith($this->server->mock());
        $this->module2->initialize->calledWith($this->server->mock());

        $this->assertTrue($result);
    }

    public function testInitializeWithFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->initialize->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->initialize($this->server->mock());

        $this->module1->initialize->calledWith($this->server->mock());
        $this->module2->initialize->calledWith($this->server->mock());
        $this->module3->initialize->never()->called();

        $this->logger->critical->calledWith(
            'Failed to initialize server module "{name}": {message}',
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
            'Failed to shut down server module "{name}": {message}',
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
            'Failure in server module "{name}": {message}',
            [
                'name' => '<module 2>',
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }
}
