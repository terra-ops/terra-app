<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Model\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


class AppAddCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('app:add')
      ->setDescription('Adds a new app.')
      ->addArgument(
        'name',
        InputArgument::OPTIONAL,
        'The name of your app.'
      )
      ->addArgument(
        'repo',
        InputArgument::OPTIONAL,
        'The URL of your git repo for your app.'
      )
      ->addOption(
        'description',
        '',
        InputArgument::OPTIONAL,
        'The name of your app.'
      )
      ->addOption(
        'create-environment',
        '',
        InputArgument::OPTIONAL,
        'Whether or not to create an environment.'
      )
      ->addOption(
        'environment-name',
        '',
        InputArgument::OPTIONAL,
        'If creating an environment, you can optionally specify a name.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    // App Name
    $name = $input->getArgument('name');
    if (empty($name)) {
      $question = new Question('System name of your project? ', '');
      $name = $helper->ask($input, $output, $question);
    }

    // App Description
    $description = $input->getOption('description');
    if (empty($description)) {
      $question = new Question('Description? ', '');
      $description = $helper->ask($input, $output, $question);
    }

    // App Source
    $repo = $input->getArgument('repo');
    if (empty($repo)) {
      $question = new Question('Source code repository URL? ', '');
      $repo = $helper->ask($input, $output, $question);
    }

    $app = new App($name, $repo, $description);
    $this->director->config['apps'][$name] = (array) $app;

    $output->writeln("OK Saving app $name");

    $this->director->saveData();

    // Confirmation
    $question = new ConfirmationQuestion("Create an environment? ", false);
    if ( $input->getOption('create-environment') || $helper->ask($input, $output, $question)) {
      // Run environment:add command.
      $command = $this->getApplication()->find('environment:add');

      $arguments = array(
        'app' => $name,
        'name' => $input->getOption('environment-name'),
      );

      $input = new ArrayInput($arguments);
      $command->run($input, $output);
    }
  }
}