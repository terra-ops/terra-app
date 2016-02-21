<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Model\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ServerStackCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('server:stack')
      ->setDescription("Add or remove services to a server's stack.")
      ->addArgument(
        'server',
        InputArgument::OPTIONAL,
        'The server to add the service to.'
      )
      ->addArgument(
        'service',
        InputArgument::OPTIONAL,
        'The service to add.'
      )
      ->addOption(
        'remove',
        null,
        InputOption::VALUE_OPTIONAL,
        'If set, the service will be removed.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    // If there are no service, Ask to add a service
    if (empty($this->director->config['services'])) {
      $question = new ConfirmationQuestion("You have no registered services.  Add one now?", false);
      if (!$helper->ask($input, $output, $question)) {
        return;
      }
      $command = $this->getApplication()->find('service:add');
      $arguments = array(
        'command' => 'service:add',
      );
      $input = new ArrayInput($arguments);
      $returnCode = $command->run($input, $output);
      if ($returnCode != 0) {
        throw new \Exception('service:add command failed.');
      }
    }

    // Get server
    $server_name = $input->getArgument('server');
    if (empty($server_name)) {
      $question = new ChoiceQuestion(
        'Which server? ',
        array_keys($this->director->config['servers']),
        0
      );
      $server_name = $helper->ask($input, $output, $question);
    }


    // Get service
    $service_name = $input->getArgument('service');
    if (empty($service_name)) {
      $question = new ChoiceQuestion(
        'Which service? ',
        array_keys($this->director->config['services']),
        0
      );
      $service_name = $helper->ask($input, $output, $question);
    }

    $server = $this->director->getServer($server_name);
    $service = $this->director->getService($service_name);

    // Verify server and role is available.
    if (!$server) {
      throw new \Exception("Server '{$server_name}' not found");
    }
    if (!$service) {
      throw new \Exception("Service '{$service_name}' not found");
    }

    $this->director->config['servers'][$server_name]['services'][$service_name] = $service_name;
    $this->director->saveData();

    $output->writeln("Service {$service_name} added to {$server_name}.");
    $output->writeln("Run 'director direct' to apply changes");
    $output->writeln("Run 'director status' to view current registry.");
  }
}