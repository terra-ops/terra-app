<?php

namespace terra\Command;

use terra\Config;

use Robo\Tasks;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Command\Command as CommandBase;

use terra\Factory\EnvironmentFactory;

/**
 * Class Command.
 */
class Command extends Tasks {

    protected $app;
    protected $environment;
  
  /**
   * Holds Talos configuration settings.
   *
   * @var Config
   */
  public $config;
  
  function __construct()
  {
    // Load config
    $this->loadConfig();
    
    // Detect currently active app.
    $this->determineActiveApp();
  }
  
  
  /**
   * Loads the app and environment from the current directory.
   */
  private function determineActiveApp() {
    $cwd = getcwd();
    foreach ($this->config->get('apps') as $app) {
      if (!isset($app['environments'])) {
        $app['environments'] = array();
      }
      foreach ($app['environments'] as $environment) {
        if (strpos($cwd, $environment['path']) === 0) {
          $this->app = $app['name'];
          $this->environment = $environment['name'];
          $this->say("Using environment <comment>{$this->app} {$this->environment}</comment>");
        }
      }
    }
  }
  
  /**
   * Load config from YML or environment variable.
   */
  private function loadConfig()
  {
    
    // Get config from env variables or files.
    if ($config_env = getenv('TERRA')) {
      $config_env = Yaml::parse($config_env);
      $config = new Config($config_env);
    } else {
      $fs = new Filesystem();
      
      $user_config = array();
      $user_config_file = getenv('HOME').'/.terra/terra.yml';
      if ($fs->exists($user_config_file)) {
        $user_config = Yaml::parse($user_config_file);
      }

//      $project_config = array();
//      $process = new Process('git rev-parse --show-toplevel');
//      $process->run();
//      if ($process->isSuccessful()) {
//        $project_config_file = trim($process->getOutput()).'/terra.yml';
//        if ($fs->exists($project_config_file)) {
//          $project_config = Yaml::parse($project_config_file);
//        }
//      }
//      $config = new Config($user_config, $project_config);
      
      $config = new Config($user_config);
    }
    $this->config = $config;
  }
  
  public function getAnswer(&$variable, $question) {
    if (!$variable) {
      $variable = $this->ask($question);
    }
  }
  
    /**
     * Detect app and environment from current path.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Determine app and environment from path.
        $cwd = getcwd();
        $config = $this->getApplication()->getTerra()->getConfig();

        foreach ($config->get('apps') as $app) {
            if (!isset($app['environments'])) {
              $app['environments'] = array();
            }
            foreach ($app['environments'] as $environment) {
                if (strpos($cwd, $environment['path']) === 0) {
                  
                    if ($input->hasArgument('app_name')) {
                      $input->setArgument('app_name', $app['name']);
                    }
                    
                    if ($input->hasArgument('environment_name')) {
                      $input->setArgument('environment_name', $environment['name']);
                    }

                    $environment_string = $app['name'] . ':' . $environment['name'];

                    $output->writeln("Using environment <question>$environment_string</question>");
                }
            }
        }
    }
//
//    /**
//     * Helper to ask a question only if a default argument is not present.
//     *
//     * @param InputInterface  $input
//     * @param OutputInterface $output
//     * @param Question        $question
//     *                                  A Question object
//     * @param $argument_name
//     *   Name of the argument or option to default to.
//     * @param string $type
//     *                     Either "argument" (default) or "option"
//     *
//     * @return mixed
//     *               The value derived from either the argument/option or the value.
//     */
//    public function getAnswer(InputInterface $input, OutputInterface $output, Question $question, $argument_name, $type = 'argument', $required = FALSE)
//    {
//        $helper = $this->getHelper('question');
//
//        if ($type == 'argument') {
//            $value = $input->getArgument($argument_name);
//        } elseif ($type == 'option') {
//            $value = $input->getOption($argument_name);
//        }
//
//        if (empty($value)) {
//
//            // If we are in non-interactive mode, we have no choice but to return nothing.
//            if ($input->getOption('yes')) {
//                return '';
//            }
//            if ($required) {
//                while (empty($value)) {
//                    $value = $helper->ask($input, $output, $question);
//                }
//            }
//            else {
//                $value = $helper->ask($input, $output, $question);
//            }
//        }
//
//        return $value;
//    }

    /**
     * Gets the application instance for this command.
     *
     * @return \terra\Console\Application
     *
     * @api
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

    /**
     * Helper to ask the user what app they want to work with.
     */
    public function getApp($app_name)
    {

        // If there are no apps, end command.
        if (count($this->config->get('apps')) == 0) {
            throw new \Exception('There are no apps to remove!. Use the command <info>terra app:add</info> to add your first app.');
        }

        $helper = $this->getHelper('question');

        // If no name specified provide options
        if (empty($app_name)) {
          $applications = array_flip(array_keys($this->config->get('apps')));
          foreach (array_keys($applications) as $app_key) {
              $applications[$app_key] = $app_key;
            }

            $question = new ChoiceQuestion(
                'Which app? ',
                $applications,
                null
            );
            $app_name = $helper->ask($input, $output, $question);
        }

        // If still empty throw an exception.
        if (empty($app_name)) {
            throw new \Exception("App '$app_name' not found.'");
        }
        else {
            // Set the app for this command.
            $this->app = (object) $this->config->get('apps', $app_name);
        }
    }

    /**
     * Helper to ask the user what app they want to work with.
     */
    public function getEnvironment(InputInterface $input, OutputInterface $output)
    {

        // If no app...
        if (empty($this->app)) {
            throw new \Exception('App not defined. Call Command::getApp() first.');
        }

        // If no environments:
        if (count(($this->app->environments)) == 0) {
            $output->writeln("<comment>There are no environments for the app {$this->app->name}!</comment>");
            $output->writeln('Use the command <info>terra environment:add</info> to add your first environment.');
            return;
        }

        $helper = $this->getHelper('question');
        $environment_name = $input->getArgument('environment_name');

        // If no environment name specified provide options
        if (empty($environment_name)) {
            $environments = array_flip(array_keys($this->app->environments));
            foreach (array_keys($environments) as $env_key) {
              $environments[$env_key] = $env_key;
            }
            $question = new ChoiceQuestion(
                'Which environment? ',
                $environments,
                null
            );
            $environment_name = $helper->ask($input, $output, $question);
        }

        // Set the environment for this command.
        $this->environment = (object) $this->app->environments[$environment_name];
    }

    /**
     * Get an environmentFactory class
     *
     * @return \terra\Factory\EnvironmentFactory
     *
     * @api
     */
    public function getEnvironmentFactory()
    {
      return new EnvironmentFactory($this->environment, $this->app);
    }
}
