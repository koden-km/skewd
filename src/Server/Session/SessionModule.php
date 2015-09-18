<?php
namespace Skewd\Server\Session;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Skewd\Common\Session\AmqpDeclarationTrait;
use Skewd\Common\Session\AnnounceMessage;
use Skewd\Common\Session\SessionStore;
use Skewd\Server\Application\Application;
use Skewd\Server\Application\Module;

/**
 * A module provides functionality to an modular application.
 */
final class SessionModule implements Module
{
    use AmqpDeclarationTrait;

    public function __construct(SessionStore $sessionStore)
    {
        $this->sessionStore = $sessionStore;
    }
    /**
     * Get the name of the module.
     *
     * The name is a label only, the name SHOULD NOT be used as an identifier
     * for the module.
     *
     * @return string The module name.
     */
    public function name()
    {
        return 'session';
    }

    /**
     * Initialize the module.
     *
     * Any once-off initialization logic SHOULD be implemented in this method.
     *
     * The method MUST throw an exception if initialization fails.
     *
     * The module MUST allow repeat calls to tick() once initialize() has
     * completed successfully.
     *
     * @param Application $application The application under which the module is executing.
     * @param AMQPChannel $channel     A private AMQP channel for use by this module.
     *
     * @throws Exception if the module can not be initialized.
     */
    public function initialize(Application $application, AMQPChannel $channel)
    {
        $this->sessionStore->clear();

        $this->queue = $this->exclusiveQueue(
            $channel,
            function ($message) {
                $this->recv($message);
            }
        );
    }

    /**
     * Shutdown the module.
     *
     * Any once-off shutdown logic (including freeing of resources, etc) SHOULD
     * be implemented in this method.
     *
     * The method MUST allow shutdown() to be called, even if a previous call
     * to initialize() has failed.
     *
     * The method MAY throw an exception if shutdown fails.
     *
     * @throws Exception if the module can not be shutdown.
     */
    public function shutdown()
    {
    }

    /**
     * Perform the module's action.
     *
     * This method is called repeatedly while the module is executing. Hence,
     * the module MUST allow repeat calls to tick() once initialize() has
     * completed successfully.
     *
     * The module MAY throw an exception if initialization has not been
     * performed.
     *
     * The module MAY throw an exception if the module is in a critical state,
     * such as an unrecoverable error that requires re-initialization.
     *
     * @throws Exception if the module is in a critical state.
     */
    public function tick()
    {
    }

    private function recv(AMQPMessage $message)
    {
        $routingKey = $message->get('routing_key');

        if ('announce' === $routingKey) {
            $this->recvAnnounce($message);
        } elseif ('properties' === $routingKey) {
            $this->recvProperties($message);
        }
    }

    private function recvAnnounce(AMQPMessage $amqpMessage)
    {
        $message = AnnounceMessage::fromAmqpMessage($amqpMessage);

        $this->sessionStore->update(
            $message->toSession()
        );
    }

    private function recvProperties(AMQPMessage $message)
    {

    }

    private $sessionStore;
    private $queue;
}
