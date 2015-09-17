<?php
namespace Skewd\Common\Session;

interface Session
{
    /**
     * Get the session ID.
     *
     * @return string A unique ID for this session.
     */
    public function id();

    /**
     * Get the version of the session.
     *
     * The version is incremented whenever the session is modified.
     *
     * @return integer The session version.
     */
    public function version();

    /**
     * Get the session properties.
     *
     * @return array<string, mixed> The session's properties.
     */
    public function properties();

    /**
     * Get the value of a property.
     *
     * @param string $name The property name.
     *
     * @return mixed          The property value.
     * @throws LogicException if the property does not exist.
     */
    public function get($name);

    /**
     * Get the value of a property, if available.
     *
     * @param string $name   The property name.
     * @param mixed  &$value Assigned the value of the property, if present.
     *
     * @return boolean True if the property is present; otherwise, false.
     */
    public function tryGet($name, &$value);

    /**
     * Get the value of a property, or a default value if not present.
     *
     * @param string $name    The property name.
     * @param mixed  $default The value to return if the property is not present.
     *
     * @return mixed The property value, or the default value.
     */
    public function safeGet($name, $default = null);

    /**
     * Check for the presence of a property.
     *
     * @param string $name The property name.
     *
     * @return boolean True if the property is present; otherwise, false.
     */
    public function has($name);

    /**
     * Set the value of a property.
     *
     * @param string $name  The property name.
     * @param mixed  $value The property value.
     */
    public function set($name, $value);

    /**
     * Remove a property.
     *
     * @param string $name The property name.
     */
    public function remove($name);
}
