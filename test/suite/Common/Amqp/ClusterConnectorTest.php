<?php
namespace Skewd\Common\Amqp;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use Icecave\Isolator\Isolator;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;

class ClusterConnectorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Prevent shuffle() from actually shuffling so that the tests are
        // predictable ...
        $this->isolator = Phony::fullMock(Isolator::class);
        $this->isolator->shuffle->does(function () {});

        $this->connectionA = Phony::fullMock(AbstractConnection::class)->mock();
        $this->connectionB = Phony::fullMock(AbstractConnection::class)->mock();

        $this->exceptionA = Phony::fullMock([AMQPExceptionInterface::class, Exception::class])->mock();
        $this->exceptionB = Phony::fullMock([AMQPExceptionInterface::class, Exception::class])->mock();

        $this->connectorA = Phony::fullMock(Connector::class);
        $this->connectorB = Phony::fullMock(Connector::class);

        $this->connectorA->connect->returns($this->connectionA);
        $this->connectorB->connect->returns($this->connectionB);

        $this->subject = ClusterConnector::create(
            $this->connectorA->mock(),
            $this->connectorB->mock()
        );

        $this->subject->setIsolator($this->isolator->mock());
    }

    public function testConnect()
    {
        $connection = $this->subject->connect();

        $this->connectorA->connect->called();
        $this->connectorB->connect->never()->called();

        $this->assertSame(
            $this->connectionA,
            $connection
        );
    }

    public function testConnectWhenEmpty()
    {
        $this->markTestIncomplete();
    }

    public function testConnectShufflesConnectionOrder()
    {
        $this->isolator->shuffle->does(
            function (&$array) {
                $array = array_reverse($array);
            }
        );

        $connection = $this->subject->connect();

        $this->connectorA->connect->never()->called();
        $this->connectorB->connect->called();

        $this->assertSame(
            $this->connectionB,
            $connection
        );
    }

    public function testConnectWhenSomeConnectorsFail()
    {
        $this->connectorA->connect->throws($this->exceptionA);

        $connection = $this->subject->connect();

        Phony::inOrder(
            $this->connectorA->connect->called(),
            $this->connectorB->connect->called()
        );

        $this->assertSame(
            $this->connectionB,
            $connection
        );
    }

    public function testConnectWhenAllConnectorsFail()
    {
        $this->connectorA->connect->throws($this->exceptionA);
        $this->connectorB->connect->throws($this->exceptionB);

        $this->setExpectedException(AMQPExceptionInterface::class);

        try {
            $this->subject->connect();
        } catch (AMQPExceptionInterface $e) {
            Phony::inOrder(
                $this->connectorA->connect->called(),
                $this->connectorB->connect->called()
            );

            $this->assertSame(
                $this->exceptionB,
                $e
            );

            throw $e;
        }
    }

    public function testConstructorWithNoConnectors()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'At least one connector must be provided.'
        );

        new ClusterConnector([]);
    }
}
