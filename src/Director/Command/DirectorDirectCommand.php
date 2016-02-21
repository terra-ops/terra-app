<?php

namespace Director\Command;

use Director\DirectorApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\Process\Process;

use AnsibleWrapper\AnsibleWrapper;

class DirectorDirectCommand extends Command {
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure() {
    $this
      ->setName('direct')
      ->setDescription('Runs ansible on our entire inventory.')
      ->addOption(
        'force',
        '',
        InputArgument::OPTIONAL,
        'If you wish to skip confirmation and just run the playbooks.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $playbook = $this->director->configPath . '/playbook.yml';
    $inventory = $this->director->configPath . '/inventory';

    // Build command string
    $cmd = "$playbook -i $inventory";

    // If only dealing with localhost, run with sudo
    if (count($this->director->servers) == 1 && isset($this->director->servers['localhost'])) {
      $cmd .= ' --connection=local --sudo --ask-sudo-pass';
    }

    // Confirmation
    if (!$input->getOption('force')) {
      $output->writeln("Run this command?");
      $output->writeln("ansible-playbook {$cmd}");
      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion("(y/n)", false);
      if (!$helper->ask($input, $output, $question)) {
        return;
      }
    }

    // Get wrapper and run command.
    $wrapper = new AnsibleWrapper();
    $wrapper->setTimeout(0);
    $wrapper->setEnvVar('ANSIBLE_FORCE_COLOR', TRUE);
    $wrapper->streamOutput();
    $wrapper->ansible($cmd);
  }
}
