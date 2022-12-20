<?php

declare(strict_types=1);

namespace Rudder;

class Rudder
{
    private static Client $client;

    /**
     * Initializes the default client to use. Uses the libcurl consumer by default.
     *
     * @param string $secret your project's secret key
     * @param array $options passed straight to the client
     *
     * @throws RudderException
     */
    public static function init(string $secret, array $options = []): void
    {
        self::assert($secret, 'Rudder::init() requires secret');

        // Rudder: support for data_plane_url
        $optionsClone = unserialize(serialize($options));
        // check if ssl is here --> check if it is http or https
        if (isset($optionsClone['data_plane_url'])) {
            $optionsClone['data_plane_url'] = self::handleSSL($optionsClone);
        } else {
            // log error
            $errstr = ("'data_plane_url' option is required");
            throw new RudderException($errstr);
        }

        self::$client = new Client($secret, $optionsClone);
    }

    /**
     *
     * Rudder
     * checks the dataplane url format only is ssl key is present
     * @param array $options passed straight to the client
     *
     * @throws RudderException
     */
    private static function handleSSL(array $options = [])
    {
        $urlComponentArray = parse_url($options['data_plane_url']);

        if (!(isset($urlComponentArray['scheme']))) {
            $options['data_plane_url'] = 'https://' . $options['data_plane_url'] ;
        }

        if (filter_var($options['data_plane_url'], FILTER_VALIDATE_URL)) {
            $protocol = 'https';
            if (isset($options['ssl']) && $options['ssl'] == false) {
                $protocol = 'http';
            }
            $urlWithoutProtocol = self::handleUrl($options['data_plane_url'], $protocol);
            return $urlWithoutProtocol;
        } else {
            // log error
            $errstr = ("'data_plane_url' input is invalid");
            throw new RudderException($errstr);
        }
    }

    /**
     *
     * Rudder
     * checks the dataplane url format only is ssl key is present
     * @param string $data_plane_url dataplane url entered in the init() function
     * @param string $protocol the protocol needs to be used according to the ssl configuration
     *
     * @throws RudderException
     */
    private static function handleUrl($data_plane_url, $protocol)
    {
        $urlComponentArray = parse_url($data_plane_url);
        if ($urlComponentArray['scheme'] == $protocol) {
            // if the protocol does not exist then error is not thrown, rather added with https:// later on
            return preg_replace('(^https?://)', '', $data_plane_url);
        } else {
            // log error
            $errstr = ('Data plane URL and SSL options are incompatible with each other');
            throw new RudderException($errstr);
        }
    }

    /**
     * Assert `value` or throw.
     *
     * @param mixed $value
     * @param string $msg
     * @throws RudderException
     */
    private static function assert($value, string $msg): void
    {
        if (empty($value)) {
            throw new RudderException($msg);
        }
    }

    /**
     * Tracks a user action
     *
     * @param array $message
     * @return bool whether the track call succeeded
     *
     * @throws RudderException
     */
    public static function track(array $message): bool
    {
        self::checkClient();
        $event = !empty($message['event']);
        self::assert($event, 'Rudder::track() expects an event');
        self::validate($message, 'track');

        return self::$client->track($message);
    }

    /**
     * Check the client.
     *
     * @throws RudderException
     */
    private static function checkClient(): void
    {
        if (self::$client !== null) {
            return;
        }

        throw new RudderException('Rudder::init() must be called before any other tracking method.');
    }

    /**
     * Validate common properties.
     *
     * @param array $message
     * @param string $type
     * @throws RudderException
     */
    public static function validate(array $message, string $type): void
    {
        $userId = (array_key_exists('userId', $message) && (string)$message['userId'] !== '');
        $anonId = !empty($message['anonymousId']);
        self::assert($userId || $anonId, "Rudder::$type() requires userId or anonymousId");
    }

    /**
     * Tags traits about the user.
     *
     * @param array $message
     * @return bool whether the call succeeded
     *
     * @throws RudderException
     */
    public static function identify(array $message): bool
    {
        self::checkClient();
        $message['type'] = 'identify';
        self::validate($message, 'identify');

        return self::$client->identify($message);
    }

    /**
     * Tags traits about the group.
     *
     * @param array $message
     * @return bool whether the group call succeeded
     *
     * @throws RudderException
     */
    public static function group(array $message): bool
    {
        self::checkClient();
        $groupId = !empty($message['groupId']);
        self::assert($groupId, 'Rudder::group() expects a groupId');
        self::validate($message, 'group');

        return self::$client->group($message);
    }

    /**
     * Tracks a page view
     *
     * @param array $message
     * @return bool whether the page call succeeded
     *
     * @throws RudderException
     */
    public static function page(array $message): bool
    {
        self::checkClient();
        self::validate($message, 'page');

        return self::$client->page($message);
    }

    /**
     * Tracks a screen view
     *
     * @param array $message
     * @return bool whether the screen call succeeded
     *
     * @throws RudderException
     */
    public static function screen(array $message): bool
    {
        self::checkClient();
        self::validate($message, 'screen');

        return self::$client->screen($message);
    }

    /**
     * Aliases the user id from a temporary id to a permanent one
     *
     * @param array $message
     * @return bool whether the alias call succeeded
     *
     * @throws RudderException
     */
    public static function alias(array $message): bool
    {
        self::checkClient();
        $userId = (array_key_exists('userId', $message) && (string)$message['userId'] !== '');
        $previousId = (array_key_exists('previousId', $message) && (string)$message['previousId'] !== '');
        self::assert($userId && $previousId, 'Rudder::alias() requires both userId and previousId');

        return self::$client->alias($message);
    }

    /**
     * Flush the client
     *
     * @throws RudderException
     */
    public static function flush(): bool
    {
        self::checkClient();

        return self::$client->flush();
    }
}

if (!function_exists('json_encode')) {
    throw new RudderException('Rudder needs the JSON PHP extension.');
}
