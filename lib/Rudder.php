<?php

require_once __DIR__ . '/Rudder/Client.php';

class Rudder {
  private static $client;

  /**
   * Initializes the default client to use. Uses the libcurl consumer by default.
   * @param  string $secret   your project's secret key
   * @param  array  $options  passed straight to the client
   */
  public static function init($secret, $options = array()) {
    self::assert($secret, "Rudder::init() requires secret");
    // check if ssl is here --> check if it is http or https
    $this->handleSSL($options);
    self::$client = new Rudder_Client($secret, $options);
  }

  /**
   * checks the dataplane url format only is ssl key is present
   * @param  array  $options  passed straight to the client
   */
  private function handleSSL($options) {
    if(filter_var($options["data_plane_url"], FILTER_VALIDATE_URL)) {
    if($options["ssl"] == 'true') { // if ssl is true only https is expected
      if (isset($options["data_plane_url"])) {
          $options["data_plane_url"] = $this->handleUrl($options["data_plane_url"],'https');  
      }
    } else {
      // if ssl is false only http is expected
      if (isset($options["data_plane_url"])) {
        $options["data_plane_url"] = $this->handleUrl($options["data_plane_url"],'http');  
      }
    } }
}
/**
   * checks the dataplane url format only is ssl key is present
   * @param string $data_plane_url  dataplane url entered in the init() function
   * @param string $protocol the protocol needs to be used according to the ssl configuration
   */
private function handleUrl($data_plane_url, $protocol) {
  $url = parse_url($options["data_plane_url"]);
  if($url['scheme'] == $protocol){
    $options["data_plane_url"] = preg_replace("(^https?://)", "", $options["data_plane_url"] );
 } else {
   // throw error
   $errstr = ("Protocol is inappropriate wrt ssl");
   $this->handleError(400, $errstr);
 }
 return $data_plane_url;
}

  /**
   * Tracks a user action
   *
   * @param  array $message
   * @return boolean whether the track call succeeded
   */
  public static function track(array $message) {
    self::checkClient();
    $event = !empty($message["event"]);
    self::assert($event, "Rudder::track() expects an event");
    self::validate($message, "track");

    return self::$client->track($message);
  }

  /**
   * Tags traits about the user.
   *
   * @param  array  $message
   * @return boolean whether the identify call succeeded
   */
  public static function identify(array $message) {
    self::checkClient();
    $message["type"] = "identify";
    self::validate($message, "identify");

    return self::$client->identify($message);
  }

  /**
   * Tags traits about the group.
   *
   * @param  array  $message
   * @return boolean whether the group call succeeded
   */
  public static function group(array $message) {
    self::checkClient();
    $groupId = !empty($message["groupId"]);
    self::assert($groupId, "Rudder::group() expects groupId");
    self::validate($message, "group");

    return self::$client->group($message);
  }

  /**
   * Tracks a page view
   *
   * @param  array $message
   * @return boolean whether the page call succeeded
   */
  public static function page(array $message) {
    self::checkClient();
    self::validate($message, "page");

    return self::$client->page($message);
  }

  /**
   * Tracks a screen view
   *
   * @param  array $message
   * @return boolean whether the screen call succeeded
   */
  public static function screen(array $message) {
    self::checkClient();
    self::validate($message, "screen");

    return self::$client->screen($message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one
   *
   * @param  array $from      user id to alias from
   * @return boolean whether the alias call succeeded
   */
  public static function alias(array $message) {
    self::checkClient();
    $userId = !empty($message["userId"]);
    $previousId = !empty($message["previousId"]);
    self::assert($userId && $previousId, "Rudder::alias() requires both userId and previousId");

    return self::$client->alias($message);
  }

  /**
   * Validate common properties.
   *
   * @param array $msg
   * @param string $type
   */
  public static function validate($msg, $type){
    $userId = !empty($msg["userId"]);
    $anonId = !empty($msg["anonymousId"]);
    self::assert($userId || $anonId, "Rudder::${type}() requires userId or anonymousId");
  }

  /**
   * Flush the client
   */

  public static function flush(){
    self::checkClient();

    return self::$client->flush();
  }

  /**
   * Check the client.
   *
   * @throws Exception
   */
  private static function checkClient(){
    if (null != self::$client) {
      return;
    }

    throw new Exception("Rudder::init() must be called before any other tracking method.");
  }

  /**
   * Assert `value` or throw.
   *
   * @param array $value
   * @param string $msg
   * @throws Exception
   */
  private static function assert($value, $msg) {
    if (!$value) {
      throw new Exception($msg);
    }
  }
}

if (!function_exists('json_encode')) {
  throw new Exception('Rudder needs the JSON PHP extension.');
}
