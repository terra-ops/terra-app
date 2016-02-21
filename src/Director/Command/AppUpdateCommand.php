<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Model\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class AppUpdateCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('app:update')
      ->setDescription('Updates the info about an app.')
      ->addArgument(
        'app',
        InputArgument::OPTIONAL,
        'The app which you would like to update.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    if ($app_name = $input->getArgument('app')) {
    }
    else {
      $question = new ChoiceQuestion(
        'Which app would you like to update?',
        array_keys($this->director->config['apps']),
        0
      );
      $question->setErrorMessage('Color %s is invalid.');

      $app_name = $helper->ask($input, $output, $question);
      $output->writeln('You have just selected: ' . $app_name);
    }

    $app = &$this->director->config['apps'][$app_name];

    // App Name
    $question = new Question("System name of your project? ({$app['name']})", $app['name']);
    $app['name'] = $helper->ask($input, $output, $question);

    // App Description
    $question = new Question("Description? ({$app['description']})",  $app['description']);
    $app['description'] = $helper->ask($input, $output, $question);

    // App Source
    $question = new Question("Source code repository URL? ({$app['source_url']})", $app['source_url']);
    $app['source_url'] = $helper->ask($input, $output, $question);

    $this->director->saveData();
  }
}