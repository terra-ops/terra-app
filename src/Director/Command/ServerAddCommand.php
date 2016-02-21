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

class ServerAddCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('server:add')
      ->setDescription('Adds a new server.')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    // Server Hostname
    $question = new Question('Hostname of your server? It should already resolve to your servers IP: ', '');
    $hostname = $helper->ask($input, $output, $question);

    $provider = 'none';

    $server = new Server($hostname,  $provider);
    $this->director->config['servers'][$hostname] = (array) $server;

    $output->writeln("OK Saving server $hostname");
    $this->director->saveData();
  }
}