<?php

namespace Director\Model;

class Environment {
  /**
   * @var string
   * Must be unique within an app.
   */
  public $name;

  /**
   * @var string
   * Path to the source code of this environment.
   */
  public $source_path;

  /**
   * @var string
   * URL of the source code repo. Inherited from the App.
   */
  public $source_url;

  /**
   * @var string
   * The current branch or tag deployed to the environment
   */
  public $git_ref;

  /**
   * Initiate the project
   */
  public function __construct($name, $source_path, $source_url, $git_ref = '') {
    $this->name = $name;
    $this->source_path = $source_path;
    $this->source_url = $source_url;
    $this->git_ref = $git_ref;
  }
}