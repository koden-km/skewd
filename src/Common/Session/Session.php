<?php
namespace Skewd\Common\Session;

use LogicException;

/**
 * A session.
 */
final class Session
{
    /**
     * Create a new session.
     *
     * @param string                $id        The session ID.
     * @param string                $owner     The owner of this session.
     * @param array<string, string> $constants A set of constant key/value pairs associated with the session.
     * @param array<string, string> $variables A set of variable key/value pairs associated with the session.
     *
     * @return Session
     */
    public static function create(
        $id,
        $owner,
        array $constants = [],
        array $variables = []
    ) {
        return new self(
            $id,
            $owner,
            1, // version
            $constants,
            $variables
        );
    }

    /**
     * Create a session with a specific version number.
     *
     * @param string                $id         The session ID.
     * @param string                $owner      The owner of this session.
     * @param integer               $version    The session version.
     * @param array<string, string> $constants A set of constant key/value pairs associated with the session.
     * @param array<string, string> $variables A set of variable key/value pairs associated with the session.
     *
     * @return Session
     */
    public static function createAtVersion(
        $id,
        $owner,
        $version,
        array $constants = [],
        array $variables = []
    ) {
        return new self(
            $id,
            $owner,
            $version,
            $constants,
            $variables
        );
    }

    /**
     * Get the session ID.
     *
     * @return string A unique ID for this session.
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Get the session owner.
     *
     * @return string The owner of this session.
     */
    public function owner()
    {
        return $this->owner;
    }

    /**
     * Get the version of the session.
     *
     * The version is incremented whenever a session variable is modified.
     *
     * @return integer The session version.
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * Get the session constants.
     *
     * Constants are key/value pairs that CAN NOT change during the life-time of
     * the session.
     *
     * @return array<string, string> The session constants.
     */
    public function constants()
    {
        return $this->constants;
    }

    /**
     * Get the session variables.
     *
     * Variables are key/value pairs that MAY change during the life-time of the
     * session.
     *
     * @return array<string, string> The session's variables.
     */
    public function variables()
    {
        return $this->variables;
    }

    /**
     * Get the value of a session variable.
     *
     * @param string $name The variable name.
     *
     * @return string         The variable value.
     * @throws LogicException if the variable does not exist.
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        throw new LogicException(
            sprintf(
                'Session %s version %d does not contain a variable named %s.',
                $this->id,
                $this->version,
                json_encode($name)
            )
        );
    }

    /**
     * Get the value of a variable, if available.
     *
     * @param string $name   The variable name.
     * @param string &$value Assigned the value of the variable, if present.
     *
     * @return boolean True if the variable is present; otherwise, false.
     */
    public function tryGet($name, &$value)
    {
        if (array_key_exists($name, $this->variables)) {
            $value = $this->variables[$name];

            return true;
        }

        return false;
    }

    /**
     * Get the value of a variable, or a default value if not present.
     *
     * @param string $name    The variable name.
     * @param string $default The value to return if the variable is not present.
     *
     * @return mixed The variable value, or the default value.
     */
    public function safeGet($name, $default = '')
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        return $default;
    }

    /**
     * Check for the presence of a variable.
     *
     * @param string $name The variable name.
     *
     * @return boolean True if the variable is present; otherwise, false.
     */
    public function has($name)
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Set the value of a variable.
     *
     * @param string $name  The variable name.
     * @param string $value The variable value.
     *
     * @return Session The updated session.
     */
    public function set($name, $value)
    {
        if (
            array_key_exists($name, $this->variables)
            && $value === $this->variables[$name]
        ) {
            return $this;
        }

        if (!is_string($value)) {
            throw new LogicException(
                'Parameter values must be strings.'
            );
        }

        $session = clone $this;
        $session->version++;
        $session->variables[$name] = $value;

        return $session;
    }

    /**
     * Set the value of multiple variables.
     *
     * @param array<string, string> $variables An associative array of new variable values.
     *
     * @return Session The updated session.
     */
    public function setMany(array $variables)
    {
        $session = null;

        foreach ($variables as $name => $value) {
            if (
                array_key_exists($name, $this->variables)
                && $value === $this->variables[$name]
            ) {
                continue;
            } elseif (!is_string($value)) {
                throw new LogicException(
                    'Parameter values must be strings.'
                );
            } elseif (null === $session) {
                $session = clone $this;
                $session->version++;
            }

            $session->variables[$name] = $value;
        }

        if ($session) {
            return $session;
        }

        return $this;
    }

    /**
     * Replace all variables with the given variables.
     *
     * @param array<string, string> $variables An associative array of new variable values.
     *
     * @return Session The updated session.
     */
    public function replaceAll(array $variables)
    {
        if ($this->variables === $variables) {
            return $this;
        }

        foreach ($variables as $value) {
            if (!is_string($value)) {
                throw new LogicException(
                    'Parameter values must be strings.'
                );
            }
        }

        $session = clone $this;
        $session->version++;
        $session->variables = $variables;

        return $session;
    }

    /**
     * Remove a variable.
     *
     * @param string ...$names The variable name.
     *
     * @return Session The updated session.
     */
    public function remove(...$names)
    {
        if (empty($this->variables)) {
            return $this;
        }

        $variables = $this->variables;

        foreach ($names as $name) {
            unset($variables[$name]);
        }

        if (count($variables) === count($this->variables)) {
            return $this;
        }

        $session = clone $this;
        $session->version++;
        $session->variables = $variables;

        return $session;
    }

    /**
     * Remove all variables.
     *
     * @return Session the updated session.
     */
    public function clear()
    {
        if (empty($this->variables)) {
            return $this;
        }

        $session = clone $this;
        $session->version++;
        $session->variables = [];

        return $session;
    }

    /**
     * @param string                $id         The session ID.
     * @param string                $owner      The owner of this session.
     * @param integer               $version    The session version.
     * @param array<string, string> $constants A set of constant key/value pairs associated with the session.
     * @param array<string, string> $variables A set of variable key/value pairs associated with the session.
     */
    private function __construct(
        $id,
        $owner,
        $version,
        array $constants,
        array $variables
    ) {
        $this->id = $id;
        $this->owner = $owner;
        $this->version = $version;
        $this->constants = $constants;
        $this->variables = $variables;
    }

    private $id;
    private $owner;
    private $version;
    private $constants;
    private $variables;
}
