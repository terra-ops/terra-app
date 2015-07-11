<?php

namespace terra\Factory;

use GitWrapper\GitWrapper;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use TQ\Git\Repository\Repository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
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
   */
  public function __construct($environment,$app) {
    $this->environment = (object) $environment;
    $this->app = (object) $app;
    $this->name = $this->environment->name;

//    $this->loadConfig();
  }

  /**
   * Clones the source code for this project.
   */
  public function init($path = NULL){
    $path = is_null($path)? $this->environment->path: $path;

    // Check if clone already exists at this path. If so we can safely skip.
    if (file_exists($path)) {
      $wrapper = new GitWrapper();

      try {
        $working_copy = new GitWorkingCopy($wrapper, $path);
        $output = $working_copy->remote('-v');
      }
      catch (GitException $e) {
        throw new \Exception('Path already exists.');
      }

      // if repo exists in the remotes already, this working copy is ok.
      if (strpos(strtolower($output), strtolower($this->app->repo)) !== FALSE) {
        return TRUE;
      }
      else {
        throw new Exception('Git clone already exists at that path, but it is not for this app.');
      }
    }

    try {
      $wrapper = new GitWrapper();
      $wrapper->streamOutput();
      $wrapper->clone($this->app->repo, $path);
    }
    catch (\GitWrapper\GitException $e) {
      return FALSE;
    }

    chdir($path);
    $wrapper->git('branch');
    $wrapper->git('status');
    $this->loadConfig();

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

    return $this->writeConfig();
  }

  /**
   * Write the docker-compose.yml file.
   * @return bool
   */
  public function writeConfig() {

    // Create the app/environment folder
    $fs = new FileSystem;
    try {
      $fs->mkdir($this->getDockerComposePath());
    }
    catch (IOExceptionInterface $e) {
      return FALSE;
    }

    // Create the environments docker-compose file.
    $dumper = new Dumper();
    try {
      $fs->remove($this->getDockerComposePath() . '/docker-compose.yml');
      $fs->dumpFile($this->getDockerComposePath() . '/docker-compose.yml', $dumper->dump($this->getDockerComposeArray(), 10));
      return TRUE;
    } catch (IOExceptionInterface $e) {
      return FALSE;
    }
  }

  /**
   * Loads app config from environment source code into $this->config
   */
  private function loadConfig() {
    // Look for .terra.yml
    $fs = new FileSystem;
    if ($fs->exists($this->getSourcePath() . '/.terra.yml')){
      $this->config = Yaml::parse(file_get_contents($this->getSourcePath() . '/.terra.yml'));
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
    if (isset($this->environment->path)) {
      return $this->environment->path;
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

    // Run the deploy hooks, if there are any.
    if (isset($this->config['hooks']['deploy']) && !empty($this->config['hooks']['deploy'])) {

      chdir($this->getSourcePath());
      $process = new Process($this->config['hooks']['deploy']);
      $process->run(function ($type, $buffer) {
        if (Process::ERR === $type) {
          // Error
          echo $buffer;
        }
        else {
          // OK
          echo $buffer;
        }
      });
    }

    // @TODO: Save the environment
    // @TODO: Create EnvironmentFactory->save();
    // Returning the branch for now. The command is saving the info.
    return $this->getRepo()->getCurrentBranch();

  }

  public function getDockerComposePath() {
    return getenv("HOME") . '/.terra/environments/' . $this->app->name . '/' . $this->app->name . '-' . $this->environment->name;
  }

  public function getDockerComposeArray() {

    $this->getConfig();

    $source_root = $this->environment->path;
    $document_root = $this->environment->path . '/' . $this->config['document_root'];

    // Look for this users SSH public key
    // @TODO: Move ssh_authorized_keys to terra config.  Ask the user on first run.
    $ssh_key_path = $_SERVER['HOME'] . "/.ssh/id_rsa.pub";
    if (file_exists($ssh_key_path)) {
      $ssh_authorized_keys = file_get_contents($ssh_key_path);
    }

    $compose = array();
    $compose['load'] = array(
      'image' => 'tutum/haproxy',
      'environment' => array(
        'VIRTUAL_HOST' => $this->getUrl(),
      ),
      'links' => array(
        'app',
      ),
      'expose' => array(
        "80/tcp",
      ),
      'ports' => array(
        ":80",
      ),
      'restart' => 'always',
    );
    $compose['app'] = array(
      'image' => 'terra/drupal',
      'tty' => TRUE,
      'stdin_open' => TRUE,
      'links' => array(
        'database',
      ),
      'volumes' => array(
        "$document_root:/usr/share/nginx/html"
      ),
      'expose' => array(
        "80/tcp",
      ),
      'restart' => 'always',
    );
    $compose['database'] = array(
      'image' => 'mariadb',
      'tty' => TRUE,
      'stdin_open' => TRUE,
      'environment' => array(
        'MYSQL_ROOT_PASSWORD' => 'RANDOMIZEPLEASE',
        'MYSQL_DATABASE' => 'drupal',
        'MYSQL_USER' => 'drupal',
        'MYSQL_PASSWORD' => 'drupal',
      ),
    );
    $compose['drush'] = array(
      'image' => 'terra/drush',
      'tty' => TRUE,
      'stdin_open' => TRUE,
      'links' => array(
        'database',
      ),
      'ports' => array(
        ":22",
      ),
      'volumes' => array(
        "$document_root:/var/www/html",
        "$source_root:/source",
      ),
      'environment' => array(
        'AUTHORIZED_KEYS' => $ssh_authorized_keys,
      ),
    );

    // Add "app_services": Additional containers linked to the app container.
    $this->getConfig();
    if (isset($this->config['docker_compose']['app_services']) && is_array($this->config['docker_compose']['app_services'])) {
      foreach ($this->config['docker_compose']['app_services'] as $service => $info) {
        $compose['app']['links'][] = $service;

        // Look for volume paths to change
        foreach ($info['volumes'] as &$volume) {
          $volume = strtr($volume, array(
            '{APP_PATH}' => $source_root,
          ));
        }

        $compose[$service] = $info;
      }
    }

    return $compose;

  }

  public function enable() {
    if ($this->writeConfig() == FALSE) {
      return FALSE;
    }

    $process = new Process('docker-compose up -d', $this->getDockerComposePath());
    $process->setTimeout(NULL);
    $process->run(function ($type, $buffer) {
      if (Process::ERR === $type) {
        echo 'DOCKER > '.$buffer;
      } else {
        echo 'DOCKER > '.$buffer;
      }
    });
    if (!$process->isSuccessful()) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Destroy an environment.
   * @return bool|string
   */
  public function destroy() {
    $process = new Process('docker-compose kill', $this->getDockerComposePath());
    $process->setTimeout(NULL);
    $process->run(function ($type, $buffer) {
      if (Process::ERR === $type) {
        echo 'DOCKER > '.$buffer;
      } else {
        echo 'DOCKER > '.$buffer;
      }
    });
    if (!$process->isSuccessful()) {
      return FALSE;
    }
    else {
      return $process->getOutput();
    }
  }

  /**
   * Basically a wrapper for docker-compose scale
   */
  public function scale($scale) {
    $cmd = "docker-compose scale app=$scale && docker-compose up -d --no-deps load";
    $process = new Process($cmd, $this->getDockerComposePath());
    $process->setTimeout(null);
    $process->run(function ($type, $buffer) {
      if (Process::ERR === $type) {
        echo 'DOCKER > '.$buffer;
      } else {
        echo 'DOCKER > '.$buffer;
      }
    });
    if (!$process->isSuccessful()) {
      return FALSE;
    }
    else {
      return $process->getOutput();
    }
  }


  /**
   * Gets the correct IP address to reference for Docker
   * @return string|mixed
   */
  public function getIp() {
    $default_url = "localhost"; // default to localhost

    $process = new Process('docker-machine ip $(docker-machine active)');
    $process->run();
    if (!$process->isSuccessful()) {
      return $default_url;
    }

    return trim($process->getOutput());
  }

  /**
   * Gets the exposed port of the load balancer container.
   * @return bool|mixed
   */
  public function getPort() {

    $process = new Process('docker-compose port load 80', $this->getDockerComposePath());
    $process->run();
    if (!$process->isSuccessful()) {
      return FALSE;
    }
    else {
      return array_pop(explode(':', trim($process->getOutput())));
    }
  }

  /**
   * Get's the exposed port of the drush container.
   * @return bool|mixed
   */
  public function getDrushPort() {

    $process = new Process('docker-compose port drush 22', $this->getDockerComposePath());
    $process->run();
    if (!$process->isSuccessful()) {
      return FALSE;
    }
    else {
      return array_pop(explode(':', trim($process->getOutput())));
    }
  }

  /**
   * Get the system URL of an environment.
   * @return string
   */
  public function getUrl() {
    return $this->app->name . '.' . $this->name . '.' . gethostname();
  }

  /**
   * Get the current scale of the app container.
   * @return bool
   */
  public function getScale() {

    // Get current scale of app service
    $process = new Process('docker-compose ps app', $this->getDockerComposePath());
    $process->run();
    if (!$process->isSuccessful()) {
      return FALSE;
    }
    $container_list = $process->getOutput();
    $lines  = explode(PHP_EOL, $container_list);
    $app_scale = 0;
    foreach ($lines as $line) {
      if (strpos($line, "{$this->app->name}{$this->name}_app") ===0) {
        $app_scale++;
      }
    }
    return $app_scale;
  }


  /**
   * Writes a local drush alias file.
   */
  public function writeDrushAlias() {
    $drush_alias_file_path = "{$_SERVER['HOME']}/.drush/{$this->app->name}.aliases.drushrc.php";

    $drush_alias_file = array();
    $drush_alias_file[] = "<?php";

    foreach ($this->app->environments as $environment_name => $environment) {

      $factory = new EnvironmentFactory($environment, $this->app);
      $drush_alias_file[] = "\$aliases['{$environment_name}'] = array(";
      $drush_alias_file[] = "  'uri' => '{$factory->getIp()}:{$factory->getPort()}',";
      $drush_alias_file[] = "  'root' => '/var/www/html',";
      $drush_alias_file[] = "  'remote-host' => '{$factory->getIp()}',";
      $drush_alias_file[] = "  'remote-user' => 'root',";
      $drush_alias_file[] = "  'ssh-options' => '-p {$factory->getDrushPort()}',";
      $drush_alias_file[] = ");";
    }

    $fs = new FileSystem;

    try {
      $fs->dumpFile($drush_alias_file_path, implode("\n", $drush_alias_file));
      return TRUE;
    }
    catch (IOException $e) {
      return FALSE;
    }
  }
}
