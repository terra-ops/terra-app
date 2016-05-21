<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\ChoiceQuestion;
use terra\Command\Command;
use terra\Factory\EnvironmentFactory;

class EnvironmentEnable extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:enable')
        ->setDescription('Enable environment.')
        ->addArgument(
            'app_name',
            InputArgument::OPTIONAL,
            'The name of the project to enable.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name of the environment to enable.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Ask for an project and environment.
        $this->getApp($input, $output);
        $this->getEnvironment($input, $output);

        $environment_name = $this->environment->name;
        $app_name = $this->project->name;

        // Attempt to enable the environment.
        $environment_factory = new EnvironmentFactory($this->environment, $this->project);
        if (!$environment_factory->enable()) {
            $output->writeln('<error>Something went wrong, environment not enabled.</error>');

            return;
        }

        // Get new port, set new URL to environment object.
        $port = $environment_factory->getPort();
        $host = $environment_factory->getHost();
        $this->environment->url = "http://$host:$port";

        // When passing to saveEnvironment, it must have project and name properties (for now).
        $this->environment->project = $app_name;
        $this->environment->name = $environment_name;

        // Save environment metadata.
        $this->getApplication()->getTerra()->getConfig()->saveEnvironment($this->environment);
        if ($this->getApplication()->getTerra()->getConfig()->save()) {
            $output->writeln('<info>Environment enabled!</info>  Available at http://'.$environment_factory->getUrl().' and ' . $this->environment->url);
        } else {
            $output->writeln('<error>Environment info not saved.</error>');
        }

        // Write drush alias.
        $drush_alias_file_path = "{$_SERVER['HOME']}/.drush/{$app_name}.aliases.drushrc.php";
        if ($environment_factory->writeDrushAlias()) {
            $output->writeln("<info>Drush alias file created at {$drush_alias_file_path}</info>");
            $output->writeln("Wrote drush alias file to <comment>$drush_alias_file_path</comment>");
            $output->writeln("Use <info>drush {$environment_factory->getDrushAlias()}</info> to access the site.");
        } else {
            $output->writeln('<error>Unable to save drush alias.</error>');
        }

        // Run the enable hooks
        $output->writeln('');
        $output->writeln('Running <comment>ENABLE</comment> project hook...');

        $environment_factory->getConfig();
        $output->writeln('Sleeping for 5 seconds to let db server start...');
        sleep(5);
        // @TODO: Figure out how to only run this hook the first time!
        if (!empty($environment_factory->config['hooks']['enable_first'])) {
            // Output what we are running
            $formatter = $this->getHelper('formatter');
            $errorMessages = array($environment_factory->config['hooks']['enable_first']);
            $formattedBlock = $formatter->formatBlock($errorMessages, 'question');
            $output->writeln($formattedBlock);

            chdir($environment_factory->getSourcePath());
            $process = new Process($environment_factory->config['hooks']['enable_first']);
            $process->setTimeout(null);
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo $buffer;
                } else {
                    echo $buffer;
                }
            });
        }
    }
}
