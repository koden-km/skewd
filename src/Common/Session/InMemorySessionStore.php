<?php
namespace Skewd\Common\Session;

/**
 * An in-memory session store.
 */
final class InMemorySessionStore implements SessionStore
{
    /**
     * Store a session in the store.
     *
     * @param Session $session The session to store.
     */
    public function store(Session $session)
    {
        $this->sessions[$session->id()] = $session;
    }

    /**
     * Store a session in the store, unless a later version is already stored.
     *
     * @param Session $session The session to store.
     * @param Session &$latest Assigned the most recent version of the session.
     *
     * @return boolean True if $session was stored; false if a later version was already stored.
     */
    public function update(Session $session, Session &$latest = null)
    {
        $id = $session->id();

        if (isset($this->sessions[$id])) {
            $latest = $this->sessions[$id];

            if ($latest->version() > $session->version()) {
                return false;
            }
        }

        $this->sessions[$id] = $latest = $session;

        return true;
    }

    /**
     * Fetch a session from the store.
     *
     * @param string $id The session ID.
     *
     * @return Session|null The session, or null if it has not been stored.
     */
    public function get($id)
    {
        if (isset($this->sessions[$id])) {
            return $this->sessions[$id];
        }

        return null;
    }

    /**
     * Remove a session from the store.
     *
     * @param string $id The session ID.
     */
    public function remove($id)
    {
        unset($this->sessions[$id]);
    }

    private $sessions = [];
}
