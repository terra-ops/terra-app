<?php
namespace Director\Factory;
use Director\DirectorApplication;
//use Drupal\Core\Render\Element\File;
use GitWrapper\GitWrapper;
use GitWrapper\GitWorkingCopy;
use TQ\Git\Repository\Repository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

/**
 * Service for an App.
 */
class EnvironmentFactory {

  public $environment;
  public $repo;
  public $config;

  /**
   * The name of the app for this environment.
   * @var string
   */
  public $app;

  /**
   * The name of this environment.
   * @var string
   */
  public $name;

  /**
   * @param $environment
   * @param $app
   * @param \Director\DirectorApplication $director
   */
  public function __construct($environment, $app, DirectorApplication $director) {
    $this->environment = (object) $environment;
    $this->app = $app;
    $this->name = $this->environment->name;
    $this->director = $director;

    $this->loadConfig();
  }

  /**
   * Clones the source code for this project.
   */
  public function init($path){
    $wrapper = new GitWrapper();
    $wrapper->streamOutput();
    $wrapper->clone($this->environment->source_url, $path);
    chdir($path);
    $wrapper->git('branch');
    $wrapper->git('status');
    $this->loadConfig();

    // Save config to director apps registry.
    // @TODO: Improve config saving system.
    $this->director->config['apps'][$this->app]['environments'][$this->name]['config'] = $this->getConfig();
    $this->director->saveData();

    // Run the build hooks
    if (!empty($this->config['hooks']['build'])) {
      chdir($this->getSourcePath());
      $process = new Process($this->config['hooks']['build']);
      $process->run(function ($type, $buffer) {
        if (Process::ERR === $type) {
          echo $buffer;
        } else {
          echo $buffer;
        }
      });
    }
  }

  /**
   * Loads app config from environment source code into $this->config
   */
  private function loadConfig() {
    // Look for .director.yml
    $fs = new FileSystem;
    if ($fs->exists($this->getSourcePath() . '/.director.yml')){
      $this->config = Yaml::parse(file_get_contents($this->getSourcePath() . '/.director.yml'));
    }
    else {
      $this->config = NULL;
    }
  }

  /**
   * Reloads config from .director.yml file.
   */
  public function reloadConfig() {
    $this->loadConfig();
  }

  /**
   * Returns the environments config.
   */
  public function getConfig() {
    if (empty($this->config)) {
      $this->loadConfig();
    }
    return $this->config;
  }

  /**
   * Get the path to this environments source code.
   * @return string
   */
  public function getSourcePath() {
    if (isset($this->environment->source_path)) {
      return $this->environment->source_path;
    }
  }

  /**
   * Get a Repository class for this environment.
   * @return \TQ\Git\Repository\Repository
   */
  public function getRepo() {
    return Repository::open($this->getSourcePath());
  }

  /**
   * Deploy a version to an environment.
   *
   * @param $version
   *   A git branch, tag, or sha.
   */
  public function deploy($version) {

    // Checkout the branch
    $wrapper = new GitWrapper();
    $wrapper->streamOutput();
    $git = new GitWorkingCopy($wrapper, $this->getSourcePath());
    $git->checkout($version);
    $git->pull();

    // Reload config so any changes get picked up.
    $this->reloadConfig();

    // Run the deploy hooks
    chdir($this->getSourcePath());
    $process = new Process($this->config['hooks']['deploy']);
    $process->run(function ($type, $buffer) {
      if (Process::ERR === $type) {
        // Error
        echo $buffer;
      } else {
        // OK
        echo $buffer;
      }
    });

    // Save new branch to yml
    $this->director->config['apps'][$this->app]['environments'][$this->name]['git_ref'] =
      $this->getRepo()->getCurrentBranch();
    $this->director->saveData();


  }
}