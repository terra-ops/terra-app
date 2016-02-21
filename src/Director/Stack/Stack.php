<?php

namespace Director\Stack;
use Director\Factory\EnvironmentFactory;

/**
 * Class Stack
 * @package Director\Stack
 *
 * A collection of services to be used by an environment
 */
class Stack {

  public $environment;
  public $services = array();

  function __construct(EnvironmentFactory $environment) {
    $this->environment = $environment;
  }

  function getVars() {
    // Process all the services and collect the ansible variables.
  }
}