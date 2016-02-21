<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Factory\EnvironmentFactory;
use Director\Model\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
// ...

class EnvironmentAddCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('environment:add')
      ->setDescription('Adds a new environment.')
      ->addArgument(
        'app',
        InputArgument::OPTIONAL,
        'The app you would like to add an environment for.'
      )
      ->addArgument(
        'name',
        InputArgument::OPTIONAL,
        'The name of the environment.'
      )
      ->addArgument(
        'path',
        InputArgument::OPTIONAL,
        'The path to the environment.'
      )
      ->addOption(
        'init-environment',
        '',
        InputArgument::OPTIONAL,
        'Clone environment?'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // App
    $helper = $this->getHelper('question');
    $app_name = $input->getArgument('app');

    if (empty($app_name)) {
      $question = new ChoiceQuestion(
        'For which app? ',
        array_keys($this->director->config['apps']),
        0
      );
      $app_name = $helper->ask($input, $output, $question);
    }
    $app = $this->director->getApp($app_name);

    // Environment Name
    $environment_name = $input->getArgument('name');
    if (empty($environment_name)) {
      $question = new Question('Environment Name: ', '');
      $environment_name = $helper->ask($input, $output, $question);
    }

    // Path
    $path = $input->getArgument('path');
    if (empty($path)) {
      $default_path = realpath('.') . '/' . $app_name . '/' . $environment_name;
      $question = new Question("Path: ($default_path)", '');
      $path = $helper->ask($input, $output, $question);
      if (empty($path)) {
        $path = $default_path;
      }
    }

    // Check for path
    $fs = new Filesystem();
    if (!$fs->isAbsolutePath($path)) {
      $path = getcwd() . '/' . $path;
    }

    $environment = new Environment($environment_name, $path, $app->getSourceUrl());
    $this->director->config['apps'][$app_name]['environments'][$environment_name] = (array) $environment;

    // Save config
    $this->director->saveData();
    $output->writeln("OK Saving environment $environment_name");

    // Clone the apps source code to the desired path.
    $environmentFactory = new EnvironmentFactory($environment, $app_name, $this->director);
    $environmentFactory->init($path);

    // Assign Servers!
    // for each environment->config->services,
    //    lookup all servers that have the required service available.
    //    ask user which server to use for each service.
    //    save the environment's service stack.

    // Save data

    // Prompt user to run director direct to deploy the services.

  }
}