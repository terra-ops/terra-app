<?php
namespace Director\Model;

/**
 * A Service.
 */
class Service {

  /**
   * @var string
   * The service name.  Must be unique.
   */
  public $name;

  /**
   * @var string
   * The service type.
   *
   * Must be an existing ServiceType class.
   */
  public $type;

  /**
   * @var string
   * A description of this role
   */
  public $description;

  /**
   * @var string
   * The ansible galaxy role.
   */
  public $galaxy_role;

  /**
   * Initiate the Role object.
   */
  public function __construct($name, $galaxy_role, $description = NULL) {
    $this->name = $name;
    $this->galaxy_role = $galaxy_role;
    $this->description = $description;
  }
}