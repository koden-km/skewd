<?php
namespace Skewd\Collection;

use IteratorAggregate;
use LogicException;

/**
 * An immutable collection of string name/value pairs.
 */
final class AttributeCollection implements IteratorAggregate
{
    /**
     * Create an attribute collection.
     *
     * @param array<string, string> $attributes An associative array of attribute values.
     *
     * @return ArrayAttributes
     */
    public static function create(array $attributes = [])
    {
        if ($attributes) {
            foreach ($attributes as $name => $value) {
                self::validateAttributeName($name);
                self::validateAttributeValue($value);
            }

            return new self($attributes);
        }

        if (!self::$empty) {
            self::$empty = new self();
        }

        return self::$empty;
    }

    /**
     * Get an attribute value by name.
     *
     * @param string $name The attribute name.
     *
     * @return string         The attribute value.
     * @throws LogicException if the attribute does not exist.
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        throw new LogicException(
            sprintf(
                'Attribute %s does not exist.',
                json_encode($name)
            )
        );
    }

    /**
     * Get an attribute value by name, if it exists.
     *
     * @param string $name   The attribute name.
     * @param string &$value Assigned the value of the attribute, if it exists.
     *
     * @return boolean True if the attribute exists; otherwise, false.
     */
    public function tryGet($name, &$value)
    {
        if (array_key_exists($name, $this->attributes)) {
            $value = $this->attributes[$name];

            return true;
        }

        return false;
    }

    /**
     * Get an attribute value by name, or a default value if it does not exist.
     *
     * @param string $name    The attribute name.
     * @param string $default The value to return if the attribute does not exist.
     *
     * @return string The attribute value, or the default value.
     */
    public function safeGet($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Check if an attribute exists.
     *
     * @param string $name The attribute name.
     *
     * @return boolean True if the attribute exists; otherwise, false.
     */
    public function has($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Check if the collection is empty.
     *
     * @return boolean True if the collection contains at least one attribute; otherwise, false.
     */
    public function isEmpty()
    {
        return empty($this->attributes);
    }

    /**
     * Set the value of an attribute.
     *
     * Returns a collection with the modified value.
     *
     * @param string $name  The attribute name.
     * @param string $value The attribute value.
     *
     * @return AttributeCollection The updated collection.
     */
    public function set($name, $value)
    {
        if (
            array_key_exists($name, $this->attributes)
            && $this->attributes[$name] === $value
        ) {
            return $this;
        }

        self::validateAttributeName($name);
        self::validateAttributeValue($value);

        $attributes = $this->attributes;
        $attributes[$name] = $value;

        return new self($attributes);
    }

    /**
     * Set the value of multiple attributes.
     *
     * @param array<string, string> $attributes An associative array of attribute values.
     *
     * @return AttributeCollection The updated collection.
     */
    public function setMany(array $attributes)
    {
        $newAttributes = null;

        foreach ($attributes as $name => $value) {
            if (
                array_key_exists($name, $this->attributes)
                && $value === $this->attributes[$name]
            ) {
                continue;
            }

            self::validateAttributeName($name);
            self::validateAttributeValue($value);

            if (null === $newAttributes) {
                $newAttributes = $this->attributes;
            }

            $newAttributes[$name] = $value;
        }

        if ($newAttributes) {
            return new self($newAttributes);
        }

        return $this;
    }

    /**
     * Replace all attributes with the given attributes.
     *
     * @param array<string, string> $attributes An associative array of attribute values.
     *
     * @return AttributeCollection The updated collection.
     */
    public function replaceAll(array $attributes)
    {
        if ($this->attributes === $attributes) {
            return $this;
        }

        foreach ($attributes as $name => $value) {
            self::validateAttributeName($name);
            self::validateAttributeValue($value);
        }

        return new self($attributes);
    }

    /**
     * Remove one or more attributes.
     *
     * @param string ...$names The attribute name(s).
     *
     * @return AttributeCollection The updated collection.
     */
    public function remove(...$names)
    {
        if (empty($this->attributes)) {
            return $this;
        }

        $attributes = $this->attributes;

        foreach ($names as $name) {
            unset($attributes[$name]);
        }

        if (count($attributes) === count($this->attributes)) {
            return $this;
        }

        return new self($attributes);
    }

    /**
     * Remove all attributes.
     *
     * @return AttributeCollection the updated collection.
     */
    public function clear()
    {
        if (empty($this->attributes)) {
            return $this;
        }

        return new self();
    }

    /**
     * @return mixed<string, string>
     */
    public function getIterator()
    {
        foreach ($this->attributes as $name => $value) {
            yield strval($name) => $value;
        }
    }

    /**
     * @param array<string, string> $attributes An associative array of attribute values.
     */
    private function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $name
     *
     * @throws LogicException
     */
    private static function validateAttributeName($name)
    {
        if (!is_string($name)) {
            throw new LogicException('Attribute name must be a string.');
        }
    }

    /**
     * @param string $name
     *
     * @throws LogicException
     */
    private static function validateAttributeValue($value)
    {
        if (!is_string($value)) {
            throw new LogicException('Attribute value must be a string.');
        }
    }

    private static $empty;
    private $attributes;
}
