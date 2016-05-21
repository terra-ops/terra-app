<?php

namespace terra\Command\Environment;

use GitWrapper\GitWrapper;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;


use terra\Command\Command;
use terra\Factory\EnvironmentFactory;

class EnvironmentUpdate extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:update')
        ->setDescription('Updates the codebase for an environment.')
        ->addArgument(
            'project_name',
            InputArgument::OPTIONAL,
            'The name of the project.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name of the environment.'
        )
        ->addOption(
            'branch',
            '',
            InputOption::VALUE_OPTIONAL,
            'If specified, commit the changes to a new branch with this name.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello Terra!');
        $helper = $this->getHelper('question');

        // Ask for an project and environment.
        $this->getApp($input, $output);
        $this->getEnvironment($input, $output);

        // Don't continue unless we have an environment.
        if (empty($this->environment)) {
            return;
        }

        // Get Environment and Config
        $environment_factory = new EnvironmentFactory($this->environment, $this->project);
        $environment_factory->getConfig();
        $alias = $environment_factory->getDrushAlias();
        $version = $environment_factory->environment->version;
        $path = $environment_factory->getSourcePath();

        // Commit!

        if ($input->isInteractive()) {
            $cmd = "drush $alias up";
        }
        else {
            $cmd = "drush $alias up -y";
        }

        $output->writeln("Running <fg=cyan>$cmd</> ...");

        $process = $this->getApplication()->getProcess($cmd);
        $process->setTty(true)->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        // Get desired version.
        $question = new ConfirmationQuestion('Commit all changes? ', FALSE);
        if ($helper->ask($input, $output, $question)) {
            $wrapper = new GitWrapper();
            $wrapper->streamOutput();

            $question = new Question("Branch name? <comment>[$version]</comment> ", $version);
            $branch = $this->getAnswer($input, $output, $question, 'branch', 'option');

            if ($branch != $version) {
                $wrapper->git("checkout -b {$branch}", $path);
            }
            $drush_output = 'Coming soon: captured drush output in commit messages.';

            $question = new ConfirmationQuestion('Are you sure you want to  add, commit, and push all changes? ', FALSE);
            if ($helper->ask($input, $output, $question)) {

                $wrapper->git('add -A', $path);
                $git = $wrapper->workingCopy($path);
                $git->commit('Terra Environment Update: ' . PHP_EOL . $drush_output);
                $git->push('origin', $branch);

                // @TODO: Offer to create a Pull Request!

            }
        }
    }
}
