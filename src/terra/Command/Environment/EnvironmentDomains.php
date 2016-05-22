<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use terra\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use terra\Factory\EnvironmentFactory;

// ...

class EnvironmentDomains extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:domains')
        ->setDescription('Manage the Domains that are assigned to environments.')
        ->addArgument(
            'project_name',
            InputArgument::OPTIONAL,
            'The project you would like manage the domains for.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name of the environment.'
        )
        ->addArgument(
            'action',
            InputArgument::OPTIONAL,
            'empty to list, "add" to create, "remove" to delete.'
        )
        ->addArgument(
            'domain',
            InputArgument::OPTIONAL,
            'The domain name to act on.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Check if "project" argument is an action.
        if ($input->getArgument('project_name') == 'add' || $input->getArgument('project_name') == 'remove') {
            // Move argument from project_name to action
            $input->setArgument('action', $input->getArgument('project_name'));
            $input->setArgument('project_name', '');

            // Move argument from env_name to domain
            $input->setArgument('domain', $input->getArgument('environment_name'));
            $input->setArgument('environment_name', '');
        }

        // Ask for a project.
        $helper = $this->getHelper('question');
        $this->getProject($input, $output);

        // Ask for an environment.
        $helper = $this->getHelper('question');
        $this->getEnvironment($input, $output);

        // If action argument is empty, show the list.
        if (empty($input->getArgument('action'))) {

            $environment = new EnvironmentFactory($this->environment, $this->project);
            $rows[] = array('http://'. $environment->getHost() . ':' . $environment->getPort());
            $rows[] = array('http://' . $environment->getUrl());

            // Get all domains
            foreach ($environment->environment->domains as $domain) {
                $rows[] = array('http://' . $domain);
            }

            $table = $this->getHelper('table');
            $table
                ->setHeaders(array("Domains for {$this->project->name} {$this->environment->name}"))
                ->setRows($rows)
            ;
            $table->render($output);
            return;
        }
        elseif ($input->getArgument('action') == 'add') {
            $this->executeAddDomain($input, $output);
        }
        elseif ($input->getArgument('action') == 'remove') {
            $this->executeRemoveDomain($input, $output);
        }

        // Offer to re-enable the environment
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Re-enable the environment <info>{$this->project->name}:{$this->environment->name}</info> [y/N]? ", FALSE);

        if ($helper->ask($input, $output, $question)) {
            // Run environment:enable command.
            $command = $this->getApplication()->find('environment:enable');
            $arguments = array(
                'project_name' => $this->project->name,
                'environment_name' => $this->environment->name,
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);
        }
    }

    /**
     * Add a domain.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeAddDomain(InputInterface $input, OutputInterface $output) {

        // Ask for a domain
        $domain_question = new Question('What domain would you like to add as a VIRTUAL_HOST for the server? (Do NOT include http://) ');
        $name = $this->getAnswer($input, $output, $domain_question, 'domain', 'argument', TRUE);

        // Add the domain to the domain property.
        $output->writeln("Adding domain: <info>{$name}</info>");

        $this->environment->domains[] = $name;

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Add the domain <comment>$name</comment> to the environment <info>{$this->project->name}:{$this->environment->name}</info> [y/N]? ", FALSE);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln("<fg=red>Domain not added.</>");
            $output->writeln('');
            exit;
        }

        // Save the new version to the config.
        $this->getApplication()->getTerra()->getConfig()->add('projects', array($this->project->name, 'environments', $this->environment->name), (array) $this->environment);
        $this->getApplication()->getTerra()->getConfig()->save();
        $output->writeln("<info>Domain added!</info> Changes won't take effect until the environment is restarted.");
        $output->writeln('');
    }

    /**
     * Remove a domain.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeRemoveDomain(InputInterface $input, OutputInterface $output) {

        // If no domains are left, exit
        if (empty($this->environment->domains)) {
            $output->writeln('<comment>There are no domains assigned to this environment!</comment>');
            exit;
        }

        // Ask for a domain
        if ($input->getArgument('domain') == NULL) {
            $helper = $this->getHelper('question');
            $domains = $this->environment->domains;
            $question = new ChoiceQuestion(
                'Which domain would you like to remove? ',
                $domains,
                null
            );
            $name = $helper->ask($input, $output, $question);
        }

        // Confirm removal.
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Remove the domain <comment>$name</comment> from the environment <info>{$this->project->name}:{$this->environment->name}</info> [y/N]? ", FALSE);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln("<fg=red>Domain not removed.</>");
            $output->writeln('');
            exit;
        }

        // Find and remove the domain from the config.
        $key = array_search($name, $this->environment->domains);
        unset($this->environment->domains[$key]);

        // Save the new version to the config.
        $this->getApplication()->getTerra()->getConfig()->add('projects', array($this->project->name, 'environments', $this->environment->name), (array) $this->environment);
        $this->getApplication()->getTerra()->getConfig()->save();
        $output->writeln("<info>Domain removed.</info> Changes won't take effect until the environment is restarted.");
        $output->writeln('');
    }
}
