<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
            'The name the app to enable.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name the environment to enable.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Ask for an app and environment.
        $this->getApp($input, $output);
        $this->getEnvironment($input, $output);

        $environment_name = $this->environment->name;
        $app_name = $this->app->name;

        // Ensure that URL Proxy is running and if not offer to enable it.
        $this->ensureProxy($input, $output);

        // Attempt to enable the environment.
        $environment_factory = new EnvironmentFactory($this->environment, $this->app);
        if (!$environment_factory->enable()) {
            $output->writeln('<error>Something went wrong, environment not enabled.</error>');

            return;
        }

        // Get new port, set new URL to environment object.
        $port = $environment_factory->getPort();
        $host = $environment_factory->getHost();
        $this->environment->url = "http://$host:$port";

        // When passing to saveEnvironment, it must have app and name properties (for now).
        $this->environment->app = $app_name;
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

        // Write Drupal Console site targets.
        $console_target_file_path = "{$_SERVER['HOME']}/.console/sites/terra.{$app_name}.yml";
        if ($environment_factory->writeConsoleSiteTargets()) {
            $output->writeln("<info>Drupal Console site file created at {$console_target_file_path}</info>");
            $output->writeln("Wrote Drupal Console site file to <comment>$console_target_file_path</comment>");
            $output->writeln("Use <info>drupal {$environment_factory->getDrushAlias()}</info> to access the site.");
        } else {
            $output->writeln('<error>Unable to save Drupal Console site target.</error>');
        }

        // Run the enable hooks
        $output->writeln('');
        $output->writeln('Running <comment>ENABLE</comment> app hook...');

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

    /**
     * Ensure that URL proxy is running and if not offer to enable it.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function ensureProxy(InputInterface $input, OutputInterface $output)
    {
        // Get running containers.
        $cmd = 'docker ps';
        $process = new Process($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Look for running instance of URL proxy in `docker ps` output.
        if (!strpos($process->getOutput(), 'jwilder/nginx-proxy')) {
            // If there's no running instance, offer to create one
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("URL proxy is not running, would you like to enable it now? [Y/n] ", true);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<error>Cancelled</error>');
            } else {
                $output->writeln('Starting URL proxy...');
                $command = $this->getApplication()->find('url-proxy:enable');
                $proxyInput = new StringInput('url-proxy:enable');
                $command->run($proxyInput, $output);
            }
        }
    }
}
