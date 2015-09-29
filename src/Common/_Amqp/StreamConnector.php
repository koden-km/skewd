<?php
namespace Skewd\Common\Amqp;

use Icecave\Isolator\IsolatorTrait;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPExceptionInterface;

/**
 * Creates non-SSL stream based AMQP connections, authenticated using the
 * AMQPLAIN mechanism.
 */
final class StreamConnector implements Connector
{
    /**
     * Create a stream connector.
     *
     * @param string  $host     The AMQP server hostname or IP address.
     * @param integer $port     The AMQP server port.
     * @param string  $username The username used to authenticate.
     * @param string  $password The password used to authenticate.
     * @param string  $vhost    The AMQP virtual host to use.
     *
     * @return StreamConnector
     */
    public static function create(
        $host = 'localhost',
        $port = 5672,
        $username = 'guest',
        $password = 'guest',
        $vhost = '/'
    ) {
        return new self($host, $port, $username, $password, $vhost);
    }

    /**
     * Connect to an AMQP server.
     *
     * @return AbstractConnection     The server connection.
     * @throws AMQPExceptionInterface The connection could not be established.
     */
    public function connect()
    {
        return $this->isolator()->new(
            AMQPStreamConnection::class,
            $this->host,
            $this->port,
            $this->username,
            $this->password,
            $this->vhost
        );
    }

    /**
     * Please note that this code is not part of the public API. It may be
     * changed or removed at any time without notice.
     *
     * @access private
     *
     * This constructor is public so that it may be used by auto-wiring
     * dependency injection containers. If you are explicitly constructing an
     * instance please use one of the static factory methods listed below.
     *
     * @see StreamConnector::create()
     *
     * @param string  $host     The AMQP server hostname or IP address.
     * @param integer $port     The AMQP server port.
     * @param string  $username The username used to authenticate.
     * @param string  $password The password used to authenticate.
     * @param string  $vhost    The AMQP virtual host to use.
     */
    public function __construct($host, $port, $username, $password, $vhost)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    use IsolatorTrait;

    private $host;
    private $port;
    private $username;
    private $password;
    private $vhost;
}
