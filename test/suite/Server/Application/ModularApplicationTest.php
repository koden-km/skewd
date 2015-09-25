<?php
namespace Skewd\Server\Application;

use Eloquent\Phony\Phpunit\Phony;
use ErrorException;
use Exception;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Skewd\Common\Messaging\Node;
use Skewd\Server\Process\Process;

class ModularApplicationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->process = Phony::fullMock(Process::class);
        $this->node = Phony::fullMock(Node::class);
        $this->logger = Phony::fullMock(LoggerInterface::class);

        $this->node->id->returns('<node-id>');
        $this->node->wait->returns(false);

        $this->module1 = Phony::fullMock(Module::class);
        $this->module1->name->returns('<module 1>');

        $this->module2 = Phony::fullMock(Module::class);
        $this->module2->name->returns('<module 2>');

        $this->module3 = Phony::fullMock(Module::class);
        $this->module3->name->returns('<module 3>');

        $this->subject = ModularApplication::create(
            $this->node->mock(),
            $this->logger->mock()
        );
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

        Phony::inOrder(
            $this->node->connect->called(),
            Phony::anyOrder(
                $this->module1->initialize->calledWith($this->node->mock()),
                $this->module2->initialize->calledWith($this->node->mock())
            )
        );

        $this->logger->info->calledWith(
            'Node initialized, node ID is {id}',
            ['id' => '<node-id>']
        );

        $this->assertTrue($result);
    }

    public function testInitializeWithNodeConnectionFailure()
    {
        $exception = new Exception('Failed!');
        $this->node->connect->throws($exception);

        $result = $this->subject->initialize($this->process->mock());

        $this->logger->critical->calledWith(
            'Node failed to establish an AMQP connection: {message}',
            [
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->logger->info->never()->calledWith(
            'Node initialized, node ID is {id}',
            ['id' => '<node-id>']
        );

        $this->assertFalse($result);
    }

    public function testInitializeWithModuleFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->initialize->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $result = $this->subject->initialize($this->process->mock());

        $this->module1->initialize->called();
        $this->module2->initialize->called();
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

        Phony::inOrder(
            Phony::anyOrder(
                $this->module1->shutdown->called(),
                $this->module2->shutdown->called()
            ),
            $this->node->disconnect->called()
        );

        $this->assertTrue($result);
    }

    public function testShutdownWithNodeDisconnectionFailure()
    {
        $exception = new Exception('Failed!');
        $this->node->disconnect->throws($exception);

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->shutdown();

        $this->logger->warning->calledWith(
            'Node failed to disconnect from AMQP gracefully: {message}',
            [
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testShutdownWithModuleFailure()
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

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        Phony::inOrder(
            $this->node->wait->calledWith(0.1),
            Phony::anyOrder(
                $this->module1->tick->called(),
                $this->module2->tick->called()
            )
        );

        $this->assertTrue($result);
    }

    public function testTickWithNodeWaitInteruptedBySignal()
    {
        $exception = new ErrorException('Interrupted system call');
        $this->node->wait->returns(true);

        $this->subject->add($this->module1->mock());

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        $this->module1->tick->never()->called();

        $this->assertTrue($result);
    }

    public function testTickWithNodeWaitFailure()
    {
        $exception = new Exception('Failed!');
        $this->node->wait->throws($exception);

        $this->subject->add($this->module1->mock());

        $this->subject->initialize($this->process->mock());

        $result = $this->subject->tick();

        $this->module1->tick->never()->called();

        $this->logger->critical->calledWith(
            'Node failed while waiting for activity: {message}',
            [
                'message' => 'Failed!',
                'exception' => $exception,
            ]
        );

        $this->assertFalse($result);
    }

    public function testTickWithModuleFailure()
    {
        $exception = new Exception('Failed!');
        $this->module2->tick->throws($exception);

        $this->subject->add($this->module1->mock());
        $this->subject->add($this->module2->mock());
        $this->subject->add($this->module3->mock());

        $this->subject->initialize($this->process->mock());

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
