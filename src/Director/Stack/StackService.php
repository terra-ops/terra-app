<?php

namespace Director\Stack;
use Director\Factory\EnvironmentFactory;

class StackService {

  public $type = '';
  function __construct($type) {
    $this->type = $type;
  }
}