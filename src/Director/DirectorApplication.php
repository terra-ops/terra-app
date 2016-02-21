<?php
namespace Director;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Application as BaseApplication;

use Director\Command\AppUpdateCommand;
use Director\Command\DirectorDirectCommand;
use Director\Command\ServerAddCommand;
use Director\Command\ServerRemoveCommand;
use Director\Command\ServerStackCommand;
use Director\Command\StatusCommand;
use Director\Command\AppAddCommand;
use Director\Command\AppRemoveCommand;
use Director\Command\AppInitCommand;
use Director\Command\EnvironmentAddCommand;
use Director\Command\EnvironmentAssignCommand;
use Director\Command\EnvironmentStatusCommand;
use Director\Command\EnvironmentDeployCommand;
use Director\Command\ServiceAddCommand;

use Director\Model\App;
use Director\Model\Server;
use Director\Model\Service;
use Director\Factory\AppFactory;

use Director\Config\DirectorConfigLoader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;

class DirectorApplication extends BaseApplication
{
  const NAME = 'Director';
  const VERSION = '0.1';

  /**
   * @var array
   * The Director registry.
   */
  public $apps = array();
  public $servers = array();
  public $services = array();

  /**
   * @var array
   * Raw data loaded from director.yml
   */
  public $config = array();
  public $configPath = '';

  public function __construct() {
    parent::__construct(static::NAME, static::VERSION);

    // Add available commands to this director.
    $this->add(new DirectorDirectCommand($this));
    $this->add(new StatusCommand($this));
    $this->add(new AppAddCommand($this));
    $this->add(new AppRemoveCommand($this));
    $this->add(new AppInitCommand($this));
    $this->add(new AppUpdateCommand($this));
    $this->add(new ServerAddCommand($this));
    $this->add(new ServerStackCommand($this));
    $this->add(new ServerRemoveCommand($this));
    $this->add(new EnvironmentAddCommand($this));
    $this->add(new EnvironmentAssignCommand($this));
    $this->add(new EnvironmentDeployCommand($this));
    $this->add(new EnvironmentStatusCommand($this));
    $this->add(new ServiceAddCommand($this));

    // Load Data
    $this->loadData();
    $this->saveData();
  }


  /**
   * Loads data from dataDirectories.
   *
   * If no data is found, write a data file.
   */
  private function loadData() {

    $loader = $this->getLoader();

    // Load core director config.
    $this->config = $loader->load($this->configPath . '/director.yml');
    $this->config['apps'] = $loader->load($this->configPath . '/apps.yml');
    $this->config['servers'] = $loader->load($this->configPath . '/servers.yml');
    $this->config['services'] = $loader->load($this->configPath . '/services.yml');

    // Load each available App
    foreach ($this->config['apps'] as $name => $data) {
      $this->apps[$name] = new AppFactory($name, $data, $this);
    }

    // Load each available Server
    if (is_array($this->config['servers'])){
      foreach ($this->config['servers'] as $name => $data) {
        $this->servers[$name] = new Server($data['hostname'], $data['provider'], $data['ip_addresses']);
      }
    }
    // Load each available Server
    if (is_array($this->config['services'])){
      foreach ($this->config['services'] as $name => $data) {
        $role = isset($data['galaxy_role'])? $data['galaxy_role']: '';
        $this->services[$name] = new Service($name, $role, $data['description']);
      }
    }
  }

  /**
   * Saves data to file.
   */
  public function saveData() {
    $dumper = new Dumper();

    // Save Apps
    file_put_contents($this->configPath . '/apps.yml', $dumper->dump($this->config['apps'], 10));

    // Save Servers
    file_put_contents($this->configPath . '/servers.yml', $dumper->dump($this->config['servers'], 4));

    // Save Services
    file_put_contents($this->configPath . '/services.yml', $dumper->dump($this->config['services'], 4));

    // Save Ansible Files

    // We can only provision localhost if that is our only server.
    // This is because ansible is going to SSH to it.
    // So, if there is more than one server, we remove "localhost" from
    // the inventory.

    if (count($this->config['servers']) > 1) {
      unset($this->config['servers']['localhost']);
    }

    // Build inventory file from servers.
    $inventory_file = array();
    $playbook_file = array(
      '# MANAGED BY DIRECTOR',
      '---',
    );
    foreach ($this->config['servers'] as $server_name => $server) {
      // Add server to inventory file.
      $inventory_file[] = $server_name;

      // Add to Playbook file.
      // Lists what roles are for each group.
      $playbook_file[] = "- hosts: $server_name";
      $playbook_file[] = "  user: root";

      // Add roles to playbook.
      if (isset($server['services'])){
        $playbook_file[] = "  roles:";
        foreach ($server['services'] as $service_name) {
          if (isset($this->config['services'][$service_name]['galaxy_role'])) {
            $playbook_file[] = "    - " . $this->config['services'][$service_name]['galaxy_role'];
          }
          if (isset($this->config['services'][$service_name]['playbook_file'])) {
            $playbook_file[] = "- include: " . $this->config['services'][$service_name]['playbook_file'];
          }
        }
      }

      // If a server has a vars file, add it to the
      $vars_file_path = $this->configPath . '/vars/' . $server_name . '.yml';
      if (isset($server['vars_files']) || file_exists($vars_file_path)) {
        $playbook_file[] = "  vars_files: ";

        if (isset($server['vars_files'])) {
          foreach ($server['vars_files'] as $vars_file) {
            if (file_exists($this->configPath . '/vars/' . $vars_file)) {
              $playbook_file[] = "    - " . $vars_file;
            }
          }
        }

        if (file_exists($vars_file_path)) {
          $playbook_file[] = "    - " . 'vars/' . $server_name . '.yml';
        }
      }
    }

    // Write inventory file.
    file_put_contents($this->configPath . '/inventory', implode("\n", $inventory_file));
    file_put_contents($this->configPath . '/playbook.yml', implode("\n", $playbook_file));

    // Reload data from files
    $this->loadData();
  }

  /**
   * Get an App object by name.
   * @param $name
   * @return AppFactory
   */
  public function getApp($name){
    return isset($this->apps[$name])?
      $this->apps[$name]:
      NULL;
  }

  /**
   * Get a Server object by name.
   * @param $name
   * @return Server
   */
  public function getServer($name){
    return isset($this->servers[$name])?
      $this->servers[$name]:
      NULL;
  }

  /**
   * Get a Service object by name.
   * @param $name
   * @return Service
   */
  public function getService($name){
    return isset($this->services[$name])?
      $this->services[$name]:
      NULL;
  }

  /**
   * Get a config loader object.
   *
   * @return \Symfony\Component\Config\Loader\DelegatingLoader
   * @throws \Exception
   */
  private function getLoader() {

    // Default to the home folder.
    $data_directories[] = $GLOBALS['_SERVER']['HOME'] . '/.director';

    // Attempt to locate data file
    $locator = new FileLocator($data_directories);
    try {
      $this->configPath = dirname(realpath($locator->locate('director.yml')));
    }
      // If there's an exception, create a config directory.
    catch (\InvalidArgumentException $e) {
      // Copy default config template to default config directory.
      $default_path = realpath(__DIR__ . '/../../config-default');
      $cmd = "cp -r {$default_path} {$data_directories[0]}";
      system($cmd);

      // Save the configPath.  If it doesn't exist, we have a problem.
      $this->configPath = realpath($data_directories[0]);
      if (empty($this->configPath) || !file_exists($this->configPath . '/director.yml')) {
        throw new \Exception("Unable to find or create a config folder!");
      }
    }

    // YML Loader
    $loaderResolver = new LoaderResolver(array(new DirectorConfigLoader($locator)));
    return new DelegatingLoader($loaderResolver);
  }

}
