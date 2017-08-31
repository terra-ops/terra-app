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
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Service for an App.
 */
class EnvironmentFactory
{
    public $environment;
    public $repo;
    public $config;

    /**
     * The name of the app for this environment.
     *
     * @var string
     */
    public $app;

    /**
     * The name of this environment.
     *
     * @var string
     */
    public $name;

    /**
     * @param $environment
     * @param $app
     */
    public function __construct($environment, $app)
    {
        $this->environment = (object) $environment;
        $this->app = (object) $app;
        $this->name = $this->environment->name;
    }

    /**
     * Clones the source code for this project.
     */
    public function init($path = null)
    {
        $path = is_null($path) ? $this->environment->path : $path;

        // Check if clone already exists at this path. If so we can safely skip.
        if (file_exists($path)) {
            $wrapper = new GitWrapper();
            $wrapper->setTimeout(3600);

            try {
                $working_copy = new GitWorkingCopy($wrapper, $path);
                $output = $working_copy->remote('-v');
            } catch (GitException $e) {
                throw new \Exception('Path already exists.');
            }

            // if repo exists in the remotes already, this working copy is ok.
            if (strpos(strtolower($output), strtolower($this->app->repo)) !== false) {
                return true;
            } else {
                throw new Exception('Git clone already exists at that path, but it is not for this app.');
            }
        }

        try {
            // Create App folder
            mkdir($path, 0755, TRUE);
            chdir($path);

            // Clone repo
            $wrapper = new GitWrapper();
            $wrapper->setTimeout(3600);
            $wrapper->streamOutput();
            $wrapper->cloneRepository($this->app->repo, $path);

            // Checkout correct version.
            $git = new GitWorkingCopy($wrapper, $this->getSourcePath());
            $git->checkout($this->environment->version);

        } catch (\GitWrapper\GitException $e) {

            // If exception is because there is no git ref, continue.
            if (strpos($e->getMessage(), 'error: pathspec') !== 0) {
                return false;
            }
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
     *
     * @return bool
     */
    public function writeConfig()
    {

        // Create the app/environment folder
        $fs = new FileSystem();
        try {
            $fs->mkdir($this->getDockerComposePath());
        } catch (IOExceptionInterface $e) {
            return false;
        }

        // Create the environments docker-compose file.
        $dumper = new Dumper();
        try {
            $fs->remove($this->getDockerComposePath().'/docker-compose.yml');
            $fs->dumpFile($this->getDockerComposePath().'/docker-compose.yml', $dumper->dump($this->getDockerComposeArray(), 10));

            return true;
        } catch (IOExceptionInterface $e) {
            return false;
        }
    }

    /**
     * Loads app config from environment source code into $this->config.
     */
    private function loadConfig()
    {
        // Look for .terra.yml
        $fs = new FileSystem();
        if ($fs->exists($this->getSourcePath().'/.terra.yml')) {
            try {
                // Process any string replacements.
                $environment_config_string = file_get_contents($this->getSourcePath().'/.terra.yml');
                $this->config = Yaml::parse(strtr($environment_config_string, array(
                  '{{alias}}' => $this->getDrushAlias(),
                  '{{uri}}' => $this->getUrl(),
                  '{{environment}}' => $this->environment->name,
                  '{{apps}}' => $this->app->name,
                )));
            }
            catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                $this->config = null;
            }
        } else {
            $this->config = null;
        }
    }

    /**
     * Reloads config from .director.yml file.
     */
    public function reloadConfig()
    {
        $this->loadConfig();
    }

    /**
     * Returns the environments config.
     */
    public function getConfig()
    {
        if (empty($this->config)) {
            $this->loadConfig();
        }

        return $this->config;
    }

    /**
     * Get the path to this environments source code.
     *
     * @return string
     */
    public function getSourcePath()
    {
        if (isset($this->environment->path)) {
            return $this->environment->path;
        }
    }

    /**
     * Get a Repository class for this environment.
     *
     * @return \TQ\Git\Repository\Repository
     */
    public function getRepo()
    {
        return Repository::open($this->getSourcePath());
    }

    /**
     * Deploy a version to an environment.
     *
     * @param $version
     *   A git branch, tag, or sha.
     */
    public function deploy($version)
    {

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
                } else {
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

    /**
     * Get's the path to this environments docker-compose file.
     *
     * This is just a temporary solution. We should probably just make a generic
     * "environment_config_folder" that decides where to put any generated config
     * for any environment using any provider.
     *
     * @return string
     */
    public function getDockerComposePath()
    {
        return getenv('HOME').'/.terra/environments/'.$this->app->name.'/'.$this->app->name.'-'.$this->environment->name;
    }

    /**
     * Generates an array that will be converted to the `docker-compose.yml` file
     * for this environment.
     *
     * @return array
     */
    public function getDockerComposeArray()
    {
        $this->getConfig();

        $source_root = $this->environment->path;

        if (!empty($this->config['document_root'])) {
            $document_root_relative = $this->config['document_root'];
            $document_root = $this->environment->path.'/'.$this->config['document_root'];
        } else {
            $document_root_relative = '';
            $document_root = $source_root;
        }

        // Look for this users SSH public key
        // @TODO: Move ssh_authorized_keys to terra config.  Ask the user on first run.
        $ssh_key_path = $_SERVER['HOME'].'/.ssh/id_rsa.pub';
        if (file_exists($ssh_key_path)) {
            $ssh_authorized_keys = file_get_contents($ssh_key_path);
        }
        else {
            $ssh_authorized_keys = '';
        }

        // Get Virtual Hosts array
        $hosts = $this->getUrl();

        if (!empty($this->environment->domains)) {
            $hosts .= ',' . implode(',', $this->environment->domains);
        }

        $environment_label = $this->app->name . ':' .
$this->environment->name;

        $compose = array();
        $compose['load'] = array(
            'image' => 'tutum/haproxy',
            'environment' => array(
                'VIRTUAL_HOST' => $hosts,
            ),
            'links' => array(
                'app',
            ),
            'expose' => array(
                '80/tcp',
            ),
            'ports' => array(
                '80',
            ),
        );
        $compose['app'] = array(
            'image' => 'terra/drupal',
            'tty' => true,
            'stdin_open' => true,
            'links' => array(
                'database',
            ),
            'volumes' => array(
                "{$this->environment->path}:/app:Z",
            ),
            'environment' => array(
                'HOST_UID' => posix_getuid(),
                'HOST_GID' => posix_getgid(),
                'DOCUMENT_ROOT' => $document_root_relative,
            ),
            'expose' => array(
                '80/tcp',
            ),
        );
        $compose['database'] = array(
            'image' => 'mariadb',
            'tty' => true,
            'stdin_open' => true,
            'environment' => array(
                'MYSQL_ROOT_PASSWORD' => 'RANDOMIZEPLEASE',
                'MYSQL_DATABASE' => 'drupal',
                'MYSQL_USER' => 'drupal',
                'MYSQL_PASSWORD' => 'drupal',
            ),
        );
        $compose['drush'] = array(
            'image' => 'terra/drush',
            'tty' => true,
            'stdin_open' => true,
            'links' => array(
                'database',
            ),
            'ports' => array(
                '22',
            ),
            'volumes' => array(
                "$document_root:/var/www/html:Z",
                "$source_root:/app:Z",
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
                if (isset($info['volumes'])) {
                    foreach ($info['volumes'] as &$volume) {
                        $volume = strtr($volume, array(
                          '{APP_PATH}' => $source_root,
                        ));
                    }
                }

                $compose[$service] = $info;
            }
        }

        // Add "overrides" to docker-compose.
        if (isset($this->config['docker_compose']['overrides']) && is_array($this->config['docker_compose']['overrides'])) {
            foreach ($this->config['docker_compose']['overrides'] as $service => $service_info) {

                // For each service, loop through properties.
                if (isset($compose[$service])) {
                    foreach ($service_info as $property_name => $property_value) {

                        // If the property is an array (like environment variables) merge it.
                        if (is_array($property_value)) {
                            $compose[$service][$property_name] = array_merge_recursive($compose[$service][$property_name], $property_value);
                        }
                        // If property is not an array, replace it.
                        else {
                            $compose[$service][$property_name] = $property_value;
                        }
                    }
                }
                else {
                    $compose[$service] = $service_info;
                }
            }
        }

        // Set global service config options
        foreach ($compose as $name => $service) {
            $compose[$name]['restart'] = 'on-failure';

            $compose[$name]['labels']['io.rancher.stack.name'] = "terra_{$this->app->name}_{$this->environment->name}";
            $compose[$name]['labels']['io.rancher.stack_service.name'] = "terra_{$this->app->name}_{$this->environment->name}/{$name}";

            $compose[$name]['labels']['io.rancher.container.network'] = 'TRUE';
        }

        return $compose;
    }

    /**
     * Turns on an environment.
     *
     * In this class, we use `docker-compose up`.
     *
     * @return bool
     */
    public function enable()
    {
        if ($this->writeConfig() === false) {
            return false;
        }

        $process = new Process('docker-compose up -d', $this->getDockerComposePath());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'DOCKER > '.$buffer;
            } else {
                echo 'DOCKER > '.$buffer;
            }
        });
        if ($process->isSuccessful()) {
            return true;
        }
    }

    /**
     * Turns off an environment.
     *
     * In this class, we use `docker-compose up`.
     *
     * @return bool
     */
    public function disable()
    {
        if ($this->writeConfig() === false) {
            return false;
        }

        $process = new Process('docker-compose stop', $this->getDockerComposePath());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'DOCKER > '.$buffer;
            } else {
                echo 'DOCKER > '.$buffer;
            }
        });
        if ($process->isSuccessful()) {
            return true;
        }
    }

    /**
     * Destroy an environment.
     *
     * @return bool|string
     */
    public function destroy()
    {

        // Run docker-compose kill
        echo "\n";
        echo "Running 'docker-compose kill' in ".$this->getDockerComposePath()."\n";
        $process = new Process('docker-compose kill', $this->getDockerComposePath());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'DOCKER > '.$buffer;
            } else {
                echo 'DOCKER > '.$buffer;
            }
        });

        // Run docker-compose rm
        echo "\n";
        echo "Running 'docker-compose rm -f' in ".$this->getDockerComposePath()."\n";
        $process = new Process('docker-compose rm -f', $this->getDockerComposePath());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'DOCKER > '.$buffer;
            } else {
                echo 'DOCKER > '.$buffer;
            }
        });
        // @TODO: Remove ~/.terra/environments/* folder.
    }

    /**
     * Basically a wrapper for docker-compose scale.
     */
    public function scale($scale)
    {
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
            return false;
        } else {
            return $process->getOutput();
        }
    }

    /**
     * Get's the exposed port of the load balancer container.
     *
     * @return bool|mixed
     */
    public function getPort()
    {
        $process = new Process('docker-compose port load 80', $this->getDockerComposePath());
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        } else {
            $output_array = explode(':', trim($process->getOutput()));
            return array_pop($output_array);
        }
    }

    /**
     * Gets the application host.
     *
     * @return bool|mixed
     */
    public function getHost() {
        return $this->app->host;
    }

    /**
     * Get's the exposed port of the drush container.
     *
     * @return bool|mixed
     */
    public function getDrushPort()
    {
        $process = new Process('docker-compose port drush 22', $this->getDockerComposePath());
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        } else {
            $output_array = explode(':', trim($process->getOutput()));
            return array_pop($output_array);
        }
    }

    /**
     * Get the system URL of an environment.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->app->name.'.'.$this->name.'.'.$this->app->host;
    }

    /**
     * Get the current scale of the app container.
     *
     * @return bool
     */
    public function getScale()
    {

        // Get current scale of app service
        $process = new Process('docker-compose ps -q app', $this->getDockerComposePath());
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        }
        $container_list = trim($process->getOutput());
        $lines = explode(PHP_EOL, $container_list);
        $app_scale = count($lines);
        return $app_scale;
    }

    /**
     * Writes a local drush alias file.
     */
    public function writeDrushAlias()
    {
        $drush_alias_file_path = "{$_SERVER['HOME']}/.drush/terra.{$this->app->name}.aliases.drushrc.php";

        $drush_alias_file = array();
        $drush_alias_file[] = '<?php';

        foreach ($this->app->environments as $environment_name => $environment) {
            $factory = new self($environment, $this->app);
            $path = "/app/" . $environment['document_root'];
            $drush_alias_file[] = '// DO NOT EDIT. This is generated by Terra. Any changes will be overridden when the environment is re-enabled.';
            $drush_alias_file[] = "\$aliases['{$environment_name}'] = array(";
            $drush_alias_file[] = "  'uri' => '{$factory->getHost()}:{$factory->getPort()}',";
            $drush_alias_file[] = "  'root' => '$path',";
            $drush_alias_file[] = "  'remote-host' => '{$factory->getHost()}',";
            $drush_alias_file[] = "  'remote-user' => 'terra',";
            $drush_alias_file[] = "  'ssh-options' => '-p {$factory->getDrushPort()}',";
            $drush_alias_file[] = ');';
        }

        $fs = new FileSystem();

        try {
            $fs->dumpFile($drush_alias_file_path, implode("\n", $drush_alias_file));

            return true;
        } catch (IOException $e) {
            return false;
        }
    }

    /**
     * Writes a local Drupal Console site file.
     */
    public function writeConsoleSiteTargets()
    {
        $console_target_file_path = "{$_SERVER['HOME']}/.console/sites/terra.{$this->app->name}.yml";

        $console_target_file = array();
        $console_target_file[] = '';

        foreach ($this->app->environments as $environment_name => $environment) {
            $factory = new self($environment, $this->app);
            $path = "/source/" . $environment['document_root'];
            $console_target_file[] = '# DO NOT EDIT. This is generated by Terra. Any changes will be overridden when the environment is re-enabled.';
            $console_target_file[] = "{$environment_name}:";
            $console_target_file[] = "  host: {$factory->getHost()}";
            $console_target_file[] = "  root: $path";
            $console_target_file[] = "  user: root";
            $console_target_file[] = "  port: {$factory->getDrushPort()}";
            $console_target_file[] = '';
        }

        $fs = new FileSystem();

        try {
            $fs->dumpFile($console_target_file_path, implode("\n", $console_target_file));

            return true;
        } catch (IOException $e) {
            return false;
        }
    }

    /**
     * Get the name of the drush alias.
     */
    public function getDrushAlias() {
        return "@terra.{$this->app->name}.{$this->environment->name}";
    }

    /**
     * Get the path to document root
     */
    public function getDocumentRoot() {
        return $this->environment->path . '/' . $this->config['document_root'];
    }

    /**
     * Runs a drush command for a specified alias.
     */
    public function runDrushCommand($drush_command, $alias = NULL) {

        if ($alias == NULL) {
            $alias = $this->getDrushAlias();
        }

        $cmd = "drush {$alias} $drush_command";

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run();
        return trim($process->getOutput());
    }

    /**
     * Generates the `.terra.yml` file for this environment.
     *
     * @return string
     */
    public function getTerraYmlContent()
    {
        $dumper = new Dumper();

        // A mix of comments and YAML output.
        $content = "# The relative path to your exposed web files.\n";
        $input = array('document_root' => $this->config['document_root']);
        $content .= $dumper->dump($input, 10);

        return $content;
    }

    /**
     * Write the `.terra.yml` file.
     *
     * @return bool
     */
    public function writeTerraYml()
    {
        // Create the environment's terra.yml file.
        $fs = new Filesystem();
        try {
            $fs->dumpFile($this->getSourcePath().'/.terra.yml', $this->getTerraYmlContent());
            return true;
        } catch (IOExceptionInterface $e) {
            return false;
        }
    }
}
