<?php

namespace terra\Command\Project;

use terra\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class AppAdd extends Command
{
    protected function configure()
    {
        $this
        ->setName('project:add')
        ->setDescription('Adds a new project.')
        ->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'The name of your project.'
        )
        ->addArgument(
            'repo',
            InputArgument::OPTIONAL,
            'The URL of your git repo for your project.'
        )
        ->addOption(
            'description',
            '',
            InputArgument::OPTIONAL,
            'The description of your project.'
        )
        ->addOption(
            'host',
            '',
            InputArgument::OPTIONAL,
            'The host of your project'
        )
        ->addOption(
            'create-environment',
            '',
            InputArgument::OPTIONAL,
            'Whether or not to create an environment.'
        )
        ->addOption(
            'environment-name',
            '',
            InputArgument::OPTIONAL,
            'If creating an environment, you can optionally specify a name.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        // Questions.
        $name_question = new Question('System name of your project? ', '');
        $description_question = new Question('Description? ', '');
        $repo_question = new Question('Source code repository URL? ', '');
        $host_default = getenv('DOCKER_HOST') ? parse_url(getenv('DOCKER_HOST'), PHP_URL_HOST) : php_uname('n');

        // Allow local.computer
        if ($host_default == '192.168.99.100') {
            $host_default = 'local.computer';
        }
        $host_question = new Question('Host? Terra uses this to show links to your environments. It must resolve to the docker machine you are working with. [' . $host_default . '] ', $host_default);

        // Prompts.
        $name = $this->getAnswer($input, $output, $name_question, 'name');
        $description = $this->getAnswer($input, $output, $description_question, 'description', 'option');
        $repo = $this->getAnswer($input, $output, $repo_question, 'repo');
        $host = $this->getAnswer($input, $output, $host_question, 'host', 'option');

        // Confirmation
        $formatter = $this->getHelper('formatter');
        $lines = array(
          "Name:        $name",
            "Description: $description",
            "Repo:        $repo",
            "Host:        $host"
        );
        $formattedBlock = $formatter->formatBlock($lines, 'fg=black;bg=green');
        $output->writeln($formattedBlock);

        $project = array(
          'name' => $name,
            'description' => $description,
            'repo' => $repo,
            'host' => $host
        );
        $this->getApplication()->getTerra()->getConfig()->add('projects', $name, $project);

        if ($this->getApplication()->getTerra()->getConfig()->save()) {
            $output->writeln('<info>Project saved</info>');
        } else {
            $output->writeln('<error>Project not saved!</error>');
        }


        // Offer to enable the environment (only if interactive.)
        if ($input->isInteractive()) {
            return;
        }

        $question = new ConfirmationQuestion("Create an environment? [y\N] ", false);
        if ($input->getOption('create-environment') || $helper->ask($input, $output, $question)) {

          // Run environment:add command.
          $command = $this->getApplication()->find('environment:add');
          $arguments = array(
            'project_name' => $project['name'],
          );
          $input = new ArrayInput($arguments);
          $command->run($input, $output);
        }
    }
}
