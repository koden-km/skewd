<?php
namespace Skewd\Common\Amqp;

use Icecave\Isolator\IsolatorTrait;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Create a non-SSL connection to an AMQP server, authenticated using the
 * AMQPLAIN login method.
 */
final class BasicConnectionFactory implements ConnectionFactory
{
    use IsolatorTrait;

    /**
     * @param string  $host     The AMQP server hostname or IP address.
     * @param integer $port     The AMQP server port.
     * @param string  $username The username used to authenticate.
     * @param string  $password The password used to authenticate.
     * @param string  $vhost    The AMQP virtual host to use.
     */
    public function __construct(
        $host = 'localhost',
        $port = 5672,
        $username = 'guest',
        $password = 'guest',
        $vhost = '/'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /**
     * Open a new connection to the AMQP server.
     *
     * @return AMQPStreamConnection
     */
    public function create()
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

    private $host;
    private $port;
    private $username;
    private $password;
    private $vhost;
}
