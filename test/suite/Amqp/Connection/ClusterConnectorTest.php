<?php
namespace Skewd\Amqp\Connection;

use Eloquent\Phony\Phpunit\Phony;
use Icecave\Isolator\Isolator;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class ClusterConnectorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Prevent shuffle() from actually shuffling so that the tests are
        // predictable ...
        $this->isolator = Phony::fullMock(Isolator::class);

        $this->connectionA = Phony::fullMock(Connection::class)->mock();
        $this->connectionB = Phony::fullMock(Connection::class)->mock();

        $this->exceptionA = ConnectionException::couldNotConnect();
        $this->exceptionB = ConnectionException::couldNotConnect();

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

        $this->setExpectedException(ConnectionException::class);

        try {
            $this->subject->connect();
        } catch (ConnectionException $e) {
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

    public function testConstructorWithInvalidType()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Connectors must be instances of ' . Connector::class
        );

        new ClusterConnector(['<not-a-connector>']);
    }
}
