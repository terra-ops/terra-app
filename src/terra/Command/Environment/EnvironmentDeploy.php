<?php

namespace terra\Command\Environment;

use terra\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use terra\Factory\EnvironmentFactory;

class EnvironmentDeploy extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:deploy')
        ->setDescription('Checkout a git ref for an environment and run deploy hooks.')
        ->addArgument(
            'project_name',
            InputArgument::OPTIONAL,
            'The project to lookup.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The environment to lookup.'
        )
        ->addArgument(
            'git_ref',
            InputArgument::OPTIONAL,
            'The git ref to checkout.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // Ask for a project and environment.
        $this->getProject($input, $output);
        $this->getEnvironment($input, $output);

        // Get desired version.
        $question = new Question('What version would you like to deploy? This can be any git ref. (branch, tag, or sha) ', '');
        $git_ref = $this->getAnswer($input, $output, $question, 'git_ref');

        // Notify user.
        $output->writeln("Deploying Project <info>{$this->project->name}</info> environment <comment>{$this->environment->name}</comment> to version <question> $git_ref </question> ...");

        // Ask for confirmation.
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Are you sure? This will checkout the version and run the deploy hooks in .terra.yml [y\N] ", false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Deploy Cancelled</error>');

            return;
        }

        // Run the deployment.
        $environment = new EnvironmentFactory($this->environment, $this->project);
        $this->environment->version = $environment->deploy($git_ref);

        // Save the new version to the config.
        $this->getApplication()->getTerra()->getConfig()->add('projects', array($this->project->name, 'environments', $this->environment->name), (array) $this->environment);
        $this->getApplication()->getTerra()->getConfig()->save();
    }
}
