<?php
namespace Director\Model;

/**
 * An app, website, project... whatever you call the thing you are working on.
 */
class Server {

  /**
   * @var string
   */
  public $hostname;

  /**
   * @var array
   */
  public $ip_addresses;

  /**
   * @var Provider[]
   */
  public $provider;

  /**
   * Initiate the server.
   *
   * The first time this is initiated
   */
  public function __construct($hostname, $provider_name = 'drush', $ip_addresses = array()) {
    $this->hostname = $hostname;
    $this->provider = $provider_name;
    if (!empty($hostname) && empty($ip_addresses)) {
      $this->ip_addresses[] = gethostbyname($hostname);
    }
    else {
      $this->ip_addresses[] = $ip_addresses;
    }
  }
}