<?php

namespace Director\Command;

use Director\DirectorApplication;
use Director\Model\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
// ...

class AppRemoveCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('app:remove')
      ->setDescription('Removes an app from the registry.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'The name of the app you would like to remove.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = $input->getArgument('name');

    // Confirm removal of the server.
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion("Remove app $name? ", false);
    if (!$helper->ask($input, $output, $question)) {
      return;
    }

    unset($this->director->config['apps'][$name]);
    $this->director->saveData();
  }
}