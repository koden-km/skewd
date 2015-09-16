<?php
namespace Skewd\Application;

use PhpAmqpLib\Channel\AMQPChannel;

/**
 * A module provides functionality to an modular application.
 */
interface Module
{
    /**
     * Get the name of the module.
     *
     * The name is a label only, the name SHOULD NOT be used as an identifier
     * for the module.
     *
     * @return string The module name.
     */
    public function name();

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
    public function initialize(Application $application, AMQPChannel $channel);

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
    public function shutdown();

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
    public function tick();
}
