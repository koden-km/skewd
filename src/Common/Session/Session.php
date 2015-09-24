<?php
namespace Skewd\Common\Session;

use Skewd\Common\Collection\AttributeCollection;

/**
 * A session.
 */
final class Session
{
    /**
     * Create a new session.
     *
     * @param string                   $id        The session ID.
     * @param string                   $owner     The owner of this session.
     * @param AttributeCollection|null $constants An attribute collection containing values that remain constant for the lifetime of the session.
     * @param AttributeCollection|null $variables An attribute collection containing values that may change over the lifetime of the session.
     *
     * @return Session
     */
    public static function create(
        $id,
        $owner,
        AttributeCollection $constants = null,
        AttributeCollection $variables = null
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
     * @param string                   $id        The session ID.
     * @param string                   $owner     The owner of this session.
     * @param integer                  $version   The session version.
     * @param AttributeCollection|null $constants An attribute collection containing values that remain constant for the lifetime of the session.
     * @param AttributeCollection|null $variables An attribute collection containing values that may change over the lifetime of the session.
     *
     * @return Session
     */
    public static function createAtVersion(
        $id,
        $owner,
        $version,
        AttributeCollection $constants = null,
        AttributeCollection $variables = null
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
     * @return AttributeCollection The session constants.
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
     * @return AttributeCollection The session variables.
     */
    public function variables()
    {
        return $this->variables;
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
        return $this->setVariables(
            $this->variables->set($name, $value)
        );
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
        return $this->setVariables(
            $this->variables->setMany($variables)
        );
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
        return $this->setVariables(
            $this->variables->replaceAll($variables)
        );
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
        return $this->setVariables(
            $this->variables->remove(...$names)
        );
    }

    /**
     * Remove all variables.
     *
     * @return Session the updated session.
     */
    public function clear()
    {
        return $this->setVariables(
            $this->variables->clear()
        );
    }

    /**
     * @param string                   $id        The session ID.
     * @param string                   $owner     The owner of this session.
     * @param integer                  $version   The session version.
     * @param AttributeCollection|null $constants An attribute collection containing values that remain constant for the lifetime of the session.
     * @param AttributeCollection|null $variables An attribute collection containing values that may change over the lifetime of the session.
     */
    private function __construct(
        $id,
        $owner,
        $version,
        AttributeCollection $constants = null,
        AttributeCollection $variables = null
    ) {
        $this->id = $id;
        $this->owner = $owner;
        $this->version = $version;
        $this->constants = $constants ?: AttributeCollection::create();
        $this->variables = $variables ?: AttributeCollection::create();
    }

    /**
     * @param AttributeCollection
     *
     * @return Session
     */
    private function setVariables(AttributeCollection $variables)
    {
        if ($variables === $this->variables) {
            return $this;
        }

        $session = clone $this;
        $session->version++;
        $session->variables = $variables;

        return $session;
    }

    private $id;
    private $owner;
    private $version;
    private $constants;
    private $variables;
}
