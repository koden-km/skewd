<?php
namespace Skewd\Common\Session;

/**
 * *** THIS TYPE IS NOT PART OF THE PUBLIC API ***
 *
 * @access private
 *
 * A session query message.
 *
 * Instructs the session owner to send information about a session.
 */
final class QueryMessage
{
    public $id;
    public $knownVersion;

    /**
     * @param string $id The session ID.
     * @param integer|null $knownVersion The known version of the session, if any.
     */
    public function __construct($id, $knownVersion = null)
    {
        $this->id = $id;
        $this->knownVersion = $knownVersion;
    }
}
