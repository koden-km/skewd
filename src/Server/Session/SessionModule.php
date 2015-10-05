<?php
namespace Skewd\Server\Session;

use Skewd\Common\Messaging\Node;
use Skewd\Server\Application\Application;
use Skewd\Server\Application\Module;
use Skewd\Session\InMemorySessionStore;
use Skewd\Session\SessionStore;

/**
 * A module provides functionality to an modular application.
 */
final class SessionModule implements Module
{
    public static function create(SessionStore $sessionStore = null)
    {
        return new self($sessionStore);
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
     * @param Node $node The node to use for communication.
     *
     * @throws Exception if the module can not be initialized.
     */
    public function initialize(Node $node)
    {
        $this->sessionStore->clear();
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
        $this->sessionStore->clear();
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

    public function __construct(SessionStore $sessionStore = null)
    {
        $this->sessionStore = $sessionStore ?: InMemorySessionStore::create();
    }

    private $sessionStore;
}
