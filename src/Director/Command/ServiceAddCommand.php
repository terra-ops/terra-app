<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Model\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ServiceAddCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('service:add')
      ->setDescription('Adds an available service.')
      ->addArgument(
        'name',
        InputArgument::OPTIONAL,
        'The name of the service you would like to add.'
      )
      ->addArgument(
        'galaxy_role',
        InputArgument::OPTIONAL,
        'The Ansible Galaxy role name.'
      )
      ->addOption(
        'description',
        '',
        InputOption::VALUE_OPTIONAL,
        ''
      )
      ->addOption(
        'install-role',
        '',
        InputOption::VALUE_OPTIONAL,
        'Force the installation of the ansible role.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    // Service Name
    $name = $input->getArgument('name');
    if (empty($name)) {
      $question = new Question('Service name: ', '');
      $name = $helper->ask($input, $output, $question);
    }

    // Ansible Galaxy Role
    $galaxy_role = $input->getArgument('galaxy_role');
    if (empty($galaxy_role)) {
      $question = new Question('Ansible Galaxy Role Name: ', '');
      $galaxy_role = $helper->ask($input, $output, $question);
    }

    // Description
    $description = $input->getOption('description');
    if (empty($description)) {
      $question = new Question('Description: ', '');
      $description = $helper->ask($input, $output, $question);
    }

    $service = new Service($name, $galaxy_role, $description);
    $this->director->config['services'][$name]= (array) $service;

    $output->writeln("OK Saving service $name");
    $this->director->saveData();

    // Confirmation
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion("Install this Ansible Galaxy Role? ", false);
    if ($input->getOption('install-role') || $helper->ask($input, $output, $question)) {
      system("ansible-galaxy install {$service->galaxy_role} -p {$this->director->configPath}/roles");
    }
  }
}