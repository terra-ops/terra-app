<?php
/**
 * Created by PhpStorm.
 * User: fcarey
 * Date: 9/18/15
 * Time: 11:09 PM
 */

namespace terra\Command\Environment;

use terra\Factory\EnvironmentFactory;
use terra\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;



class EnvironmentDrush extends Command {
  protected function configure() {
    $this
      ->setName('environment:drush')
      ->setDescription("Run a drush command on an environment's drush container.")
      ->addArgument(
        'project_name',
        InputArgument::REQUIRED,
        'The name of the project'
      )
      ->addArgument(
        'environment_name',
        InputArgument::REQUIRED,
        'The name of the environment'
      )
      ->addArgument(
        'commands',
        InputArgument::IS_ARRAY,
        'The command to run and its arguments'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    // Ask for an project and environment.
    $this->getApp($input, $output);
    $this->getEnvironment($input, $output);

    $environment_factory = $this->getEnvironmentFactory();
    $output->writeln('<info>Project:</info> ' . $this->project->name);
    $output->writeln('<info>Environment:</info> ' . $this->environment->name);

    $commands = implode(" ",$input->getArgument('commands'));
    $process = new Process("docker-compose -f {$environment_factory
      ->getDockerComposePath()}/docker-compose.yml run drush drush --root=/var/www/html $commands");
    echo $process->getCommandLine();

    $process->setTty(true)->run();
    // executes after the command finishes
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    echo $process->getOutput();
    return;
  }
}
