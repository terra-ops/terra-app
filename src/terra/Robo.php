<?php

namespace terra;

use Robo\Robo as RoboBase;
use Robo\Runner as RunnerBase;

class Robo extends RoboBase {

  public static function run($argv, $commandClasses, $appName = null, $appVersion = null, $output = null){
    $runner = new \terra\Runner($commandClasses);
    $statusCode = $runner->execute($argv, $appName, $appVersion, $output);
    return $statusCode;
  }
}

class Runner extends RunnerBase {
  
  /**
   * Class Constructor
   *
   * @param null|string $roboClass
   * @param null|string $roboFile
   */
  public function __construct($roboClass = null, $roboFile = null)
  {
    // set the const as class properties to allow overwriting in child classes
    $this->roboClass = $roboClass ? $roboClass : self::ROBOCLASS ;
    $this->roboFile  = $roboFile ? $roboFile : self::ROBOFILE;
    $this->dir = getcwd();
  }
  
}
