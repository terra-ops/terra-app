<?php

namespace Director\Command;

use Director\DirectorApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;

use TQ\Git\Repository\Repository;
use GitWrapper\GitWrapper;
use GitWrapper\GitWorkingCopy;
use TQ\Tests\Helper;

class EnvironmentAssignCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('environment:assign')
      ->setDescription('Choose the servers for an environment.')
      ->addArgument(
        'app',
        InputArgument::REQUIRED,
        'The app to lookup.'
      )
      ->addArgument(
        'environment',
        InputArgument::REQUIRED,
        'The environment to lookup.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $app = $this->director->getApp($input->getArgument('app'));
    $environment = $app->getEnvironment($input->getArgument('environment'));

    if (empty($environment)) {
      throw new \Exception('Environment not found: ' . $input->getArgument('environment'));
    }

    $output->writeln('<info>APP:</info> ' . $environment->app);
    $output->writeln('<info>ENVIRONMENT:</info> ' . $environment->name);
    $output->writeln('<info>REQUIRED SERVICES:</info> ' . implode(' ', $environment->config['services']));

    $question = $this->getHelper('question');

    // 1. Look for this apps service stack.
    foreach ($environment->config['services'] as $service) {
      $options = '';

        $choice_question = new ChoiceQuestion(
          "Which server for <info>$service</info>?",
          $options,
          0
        );
        $app_name = $question->ask($input, $output, $question);
    }


    // 2. Lookup servers that have available services.
    // 3. Ask the user what servers to use for each service.


  }
}