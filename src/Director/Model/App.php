<?php
namespace Director\Model;

/**
 * An app, website, project... whatever you call the thing you are working on.
 */
class App {

  /**
   * @var string
   * The app's machine name.  Must be unique.
   */
  public $name;

  /**
   * @var string
   * A description or title of this app.
   */
  public $description;

  /**
   * @var string
   * Source code URL.
   */
  public $source_url;

  /**
   * @var Environment[]
   */
  public $environments;

  /**
   * Initiate the app object.
   */
  public function __construct($name, $source_url, $description = NULL) {
    $this->name = $name;
    $this->description = $description;
    $this->source_url = $source_url;
  }

  public function init() {

  }
}