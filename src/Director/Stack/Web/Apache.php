<?php
namespace Director\Stack\Web;

class Apache extends WebStackService {

  /**
   * List of Director\Model\Service classes
   * @var
   */
  public $services;

  function __construct() {
    $this->services[] = 'web';
  }

}