<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\ChoiceQuestion;
use terra\Command\Command;
use terra\Factory\EnvironmentFactory;

class EnvironmentDisable extends Command
{
    protected function configure()
    {
        $this
            ->setName('environment:disable')
            ->setDescription('Disable environment.')
            ->addArgument(
                'project_name',
                InputArgument::OPTIONAL,
                'The name of the project to disable.'
            )
            ->addArgument(
                'environment_name',
                InputArgument::OPTIONAL,
                'The name of the environment to disable.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Ask for an project and environment.
        $this->getApp($input, $output);
        $this->getEnvironment($input, $output);

        $environment_name = $this->environment->name;
        $project_name = $this->project->name;

        // Attempt to disable the environment.
        $environment_factory = new EnvironmentFactory($this->environment, $this->project);
        if (!$environment_factory->disable()) {
            $output->writeln('<error>Something went wrong, environment not disabled.</error>');

            return;
        }
    }
}
