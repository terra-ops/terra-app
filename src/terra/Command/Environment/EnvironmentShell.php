<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use terra\Command\Command;

class EnvironmentShell extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:shell')
        ->setDescription('Enter a bash shell in the app container.')
        ->addArgument(
            'app_name',
            InputArgument::OPTIONAL,
            'The name the app.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name the environment.'
        )
        ->addArgument(
            'service',
            InputArgument::OPTIONAL,
            'The service to enter into. Default: app',
            'app'
        )
        ->addOption(
            'user',
            'u',
            InputOption::VALUE_OPTIONAL,
            'The user to enter into. Default: terra',
            'terra'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $this->getApp($input, $output);
      $this->getEnvironment($input, $output);
      $dir = $this->getEnvironmentFactory()->getDockerComposePath();
      $service = $input->getArgument('service');
      $user = $input->getOption('user');
      $cmd = "docker-compose exec --user $user $service bash";
      $output->writeln("Running '$cmd' in $dir");
  
      $process = new \Symfony\Component\Process\Process($cmd);
      $process->setWorkingDirectory($dir);
      $process->setTty(true);
      $process->run();
    }
}
