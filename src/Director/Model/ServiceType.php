<?php
namespace Director\Model;

/**
 * A ServiceType.
 *
 * web, db, etc
 */
class ServiceType {

  /**
   * @var string
   * The service type.
   */
  public $name;

  /**
   * @var string
   * Short description of the service type.
   */
  public $description;

  /**
   * Initiate the Role object.
   */
  public function __construct($name, $description = NULL) {
    $this->name = $name;
    $this->description = $description;
  }
}
