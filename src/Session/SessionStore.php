<?php
namespace Skewd\Session;

/**
 * A storage system for sessions.
 */
interface SessionStore
{
    /**
     * Fetch a session from the store.
     *
     * @param string $id The session ID.
     *
     * @return Session|null The session, or null if it has not been stored.
     */
    public function get($id);

    /**
     * Store a session in the store.
     *
     * @param Session $session The session to store.
     */
    public function store(Session $session);

    /**
     * Store a session in the store, unless a later version is already stored.
     *
     * @param Session $session The session to store.
     * @param Session &$latest Assigned the most recent version of the session.
     *
     * @return boolean True if $session was stored; false if a later version was already stored.
     */
    public function update(Session $session, Session &$latest = null);

    /**
     * Remove a session from the store.
     *
     * @param string $id The session ID.
     */
    public function remove($id);

    /**
     * Remove all sessions from the store.
     */
    public function clear();
}
