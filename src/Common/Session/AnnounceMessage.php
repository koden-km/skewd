<?php
namespace Skewd\Common\Session;

use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * *** THIS TYPE IS NOT PART OF THE PUBLIC API ***
 *
 * @access private
 *
 * A session announce message.
 *
 * Contains full data about a session.
 */
final class AnnounceMessage
{
    public $id;
    public $owner;
    public $version;
    public $attributes;
    public $properties;

    public function __construct(
        $id,
        $owner,
        $version,
        array $attributes,
        array $properties
    ) {
        $this->id = $id;
        $this->owner = $owner;
        $this->version = $version;
        $this->attributes = $attributes;
        $this->properties = $properties;
    }

    public static function fromSession(Session $session)
    {
        return new self(
            $session->id(),
            $session->owner(),
            $session->version(),
            $session->attributes(),
            $session->properties()
        );
    }

    public function toSession()
    {
        return Session::createAtVersion(
            $this->id,
            $this->owner,
            $this->version,
            $this->attributes,
            $this->properties
        );
    }

    public static function fromAmqpMessage(AMQPMessage $message)
    {
        $headers = $message->get('application_headers')->getNativeData();

        if (!isset($headers['id'])) {
            throw new InvalidArgumentException('Invalid announce message, missing ID header.');
        } elseif (!isset($headers['owner'])) {
            throw new InvalidArgumentException('Invalid announce message, missing owner header.');
        } elseif (!isset($headers['version'])) {
            throw new InvalidArgumentException('Invalid announce message, missing version header.');
        } elseif (!ctype_digit($headers['version'])) {
            throw new InvalidArgumentException('Invalid announce message, malformed version header.');
        }

        $body = @json_decode($message->body, true);

        if (!is_array($body)) {
            throw new InvalidArgumentException('Invalid announce message, malformed payload.');
        } elseif (count($body) !== 2) {
            throw new InvalidArgumentException('Invalid announce message, invalid payload.');
        }

        list($attributes, $properties) = $body;

        if (!is_array($attributes)) {
            throw new InvalidArgumentException('Invalid announce message, invalid attributes.');
        } elseif (!is_array($properties)) {
            throw new InvalidArgumentException('Invalid announce message, invalid properties.');
        }

        foreach ($attributes as $key => $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid announce message, attribute "%s" has non-string value.',
                        $key
                    )
                );
            }
        }

        foreach ($properties as $key => $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid announce message, property "%s" has non-string value.',
                        $key
                    )
                );
            }
        }

        return new self(
            $headers['id'],
            $headers['owner'],
            intval($headers['version']),
            $attributes,
            $properties
        );
    }

    public function toAmqpMessage()
    {
        return new AMQPMessage(
            json_encode([$this->attributes, $this->properties]),
            [
                'application_headers' => [
                    'id' => $this->id,
                    'owner' => $this->owner,
                    'version' => $this->version,
                ]
            ]
        );
    }
}
