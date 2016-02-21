<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Model\App;
use Director\Service\AppFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;


class AppInitCommand extends Command
{
  /**
   * @var \Director\DirectorApplication
   * The director application.
   */
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('app:init')
      ->setDescription('Initiate an instance of your app.')
      ->addArgument(
        'name',
        InputArgument::OPTIONAL,
        'The app you would like to initiate.'
      )
      ->addArgument(
        'path',
        InputArgument::OPTIONAL,
        'The path to clone the source code of your app.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    // Get our App
    $app_name = $input->getArgument('name');
    if (empty($app_name)) {
      $question = new ChoiceQuestion(
        'Which app would you like to clone? ',
        array_keys($this->director->config['apps']),
        0
      );
      $app_name = $helper->ask($input, $output, $question);
    }
    $app = $this->director->getApp($app_name);

    // Determine what path to init in.
    // This command acts like git clone.  $path defaults to $name.
    $full_path = $input->getArgument('path');
    if (empty($path)) {
      $full_path = $this->director->configPath . '/apps/' . $app_name . '.git';
    }

    // Confirmation
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion("Clone {$app->source_url} to {$full_path}? ", false);
    if (!$helper->ask($input, $output, $question)) {
      return;
    }

    // Initiate the app.
    $app->init($full_path);

    $this->director->config['apps'][$app_name]['source_path'] = $full_path;
    $this->director->saveData();

  }
}