<?php
namespace Skewd\Common\Session;

use LogicException;

final class Session
{
    /**
     * @param string                $id         The session ID.
     * @param array<string, string> $attributes The session attributes.
     */
    public function __construct($id, array $attributes = [])
    {
        $this->id = $id;
        $this->version = 1;
        $this->attributes = $attributes;
        $this->properties = [];
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
     * Get the version of the session.
     *
     * The version is incremented whenever the session is modified.
     *
     * @return integer The session version.
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * Get the session attributes.
     *
     * Attributes are key/value pairs that are constant for the life-time of the
     * session.
     *
     * @return array<string, string> The session's properties.
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Get the session properties.
     *
     * Properties are key/value pairs that may change over the life-time of the
     * session.
     *
     * @return array<string, string> The session's properties.
     */
    public function properties()
    {
        return $this->properties;
    }

    /**
     * Get the value of a property.
     *
     * @param string $name The property name.
     *
     * @return string         The property value.
     * @throws LogicException if the property does not exist.
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        throw new LogicException(
            sprintf(
                'Session %s version %d does not contain a property named %s.',
                $this->id,
                $this->version,
                json_encode($name)
            )
        );
    }

    /**
     * Get the value of a property, if available.
     *
     * @param string $name   The property name.
     * @param string &$value Assigned the value of the property, if present.
     *
     * @return boolean True if the property is present; otherwise, false.
     */
    public function tryGet($name, &$value)
    {
        if (array_key_exists($name, $this->properties)) {
            $value = $this->properties[$name];

            return true;
        }

        return false;
    }

    /**
     * Get the value of a property, or a default value if not present.
     *
     * @param string $name    The property name.
     * @param string $default The value to return if the property is not present.
     *
     * @return mixed The property value, or the default value.
     */
    public function safeGet($name, $default = '')
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        return $default;
    }

    /**
     * Check for the presence of a property.
     *
     * @param string $name The property name.
     *
     * @return boolean True if the property is present; otherwise, false.
     */
    public function has($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Set the value of a property.
     *
     * @param string $name  The property name.
     * @param string $value The property value.
     *
     * @return Session The updated session.
     */
    public function set($name, $value)
    {
        if (
            array_key_exists($name, $this->properties)
            && $value === $this->properties[$name]
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
        $session->properties[$name] = $value;

        return $session;
    }

    /**
     * Set the value of multiple properties.
     *
     * @param array<string, string> $properties An associative array of new property values.
     *
     * @return Session The updated session.
     */
    public function setMany(array $properties)
    {
        $session = null;

        foreach ($properties as $name => $value) {
            if (
                array_key_exists($name, $this->properties)
                && $value === $this->properties[$name]
            ) {
                continue;
            } elseif (!is_string($value)) {
                throw new LogicException(
                    'Parameter values must be strings.'
                );
            }

            if (null === $session) {
                $session = clone $this;
                $session->version++;
            } else {
                strlen('COVERAGE');
            }

            $session->properties[$name] = $value;
        }

        if ($session) {
            return $session;
        }

        return $this;
    }

    /**
     * Replace all properties with the given properties.
     *
     * @param array<string, string> $properties An associative array of new property values.
     *
     * @return Session The updated session.
     */
    public function replaceAll(array $properties)
    {
        if ($this->properties === $properties) {
            return $this;
        }

        foreach ($properties as $value) {
            if (!is_string($value)) {
                throw new LogicException(
                    'Parameter values must be strings.'
                );
            }
        }

        $session = clone $this;
        $session->version++;
        $session->properties = $properties;

        return $session;
    }

    /**
     * Remove a property.
     *
     * @param string ...$names The property name.
     *
     * @return Session The updated session.
     */
    public function remove(...$names)
    {
        if (empty($this->properties)) {
            return $this;
        }

        $properties = $this->properties;

        foreach ($names as $name) {
            unset($properties[$name]);
        }

        if (count($properties) === count($this->properties)) {
            return $this;
        }

        $session = clone $this;
        $session->version++;
        $session->properties = $properties;

        return $session;
    }

    /**
     * Remove all properties.
     *
     * @return Session the updated session.
     */
    public function clear()
    {
        if (empty($this->properties)) {
            return $this;
        }

        $session = clone $this;
        $session->version++;
        $session->properties = [];

        return $session;
    }

    private $id;
    private $version;
    private $attributes;
    private $properties;
}
