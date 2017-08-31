<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputOption;
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

class EnvironmentAdd extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:add')
        ->setDescription('Adds a new environment.')
        ->addArgument(
            'app_name',
            InputArgument::OPTIONAL,
            'The app you would like to add an environment for.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name of the environment.'
        )
        ->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the environment.'
        )
        ->addOption(
            'ref',
            'r',
            InputArgument::OPTIONAL,
            'The git branch, tag, or sha used to create the environment.'
        )
        ->addArgument(
            'document_root',
            InputArgument::OPTIONAL,
            'The path to the web document root within the repository.',
            '/'
        )
        ->addOption(
            'enable',
            'e',
            InputOption::VALUE_NONE,
            'Enable this environment immediately.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Ask for an app.
        $helper = $this->getHelper('question');
        $this->getApp($input, $output);

        // Ask for environment name
        $environment_name = $input->getArgument('environment_name');
        while (empty($environment_name) || isset($this->app->environments[$environment_name])) {
            $question = new Question('Environment name? ');
            $environment_name = $helper->ask($input, $output, $question);

            // Check for spaces or characters.
            if(!preg_match('/^[a-zA-Z0-9]+$/', $environment_name)) {
                $output->writeln("<error> ERROR </error> Environment name cannot contain spaces or special characters.");
                $environment_name = '';
                continue;
            }

            // Look for environment with this name.
            if (isset($this->app->environments[$environment_name])) {
                $output->writeln("<error> ERROR </error> Environment <comment>{$environment_name}</comment> already exists in app <comment>{$this->app->name}</comment>");
            }
        }

        // Path
        $path = $input->getArgument('path');
        if (empty($path)) {
            // Load apps base path from Config.
            $config_path = $this->getApplication()->getTerra()->getConfig()->get('apps_basepath');

            // If no apps path is set, ask for it now.
            if (empty($config_path)) {
                $default = $_SERVER['HOME'] . '/Apps';
                $question = new Question("Where would you like to store your Apps source code? [$default] ", $default);

                while (empty($config_path)) {
                    $config_path = $helper->ask($input, $output, $question);
                }

                // Save to config.
                $config = $this->getApplication()->getTerra()->getConfig();
                $config->set('apps_basepath', $config_path);
                $config->save();
            }

            // If it already exists, use "realpath" to load it.
            if (file_exists($config_path)) {
              $default_path = realpath($config_path).'/'.$this->app->name.'/'.$environment_name;
            }
            // If it doesn't exist, just use ~/Apps/$ENV as the default path.
            else {

              // Offer to create the apps path.
              $question = new ConfirmationQuestion("Default apps folder {$config_path} is missing.  Create it? [y\N] ", false);
              if ($helper->ask($input, $output, $question)) {
                mkdir($config_path);
                $default_path = $_SERVER['HOME'] . '/Apps/' . $this->app->name . '/' . $environment_name;
              }
            }
            if (!$input->getOption('yes')) {
                $question = new Question("Environment Source Code Path: ($default_path) ", $default_path);
                $path = $helper->ask($input, $output, $question);
            }
            else {
                $output->writeln("<info>Running with --yes flag. Using default path ($default_path).</info>");
                $path = $default_path;
            }
        }

        // Check for path
        $fs = new Filesystem();
        if (!$fs->isAbsolutePath($path)) {
            // Don't save the "." to the environments path.
            if ($path == '.') {
                $path = getcwd();
            }
            else {
                $path = getcwd().'/'.$path;
            }
        }

        // Ask for git version
        $version_question = new Question('Git branch or tag? [default branch] ', '');
        $version = $this->getAnswer($input, $output, $version_question, 'ref', 'option');

        // Environment object
        $environment = array(
            'app' => $this->app->name,
            'name' => $environment_name,
            'path' => $path,
            'document_root' => '',
            'url' => '',
            'port' => '',
            'version' => $version,
            'domains' => array(),
        );

        // Prepare the environment factory.
        // Clone the apps source code to the desired path.
        $environmentFactory = new EnvironmentFactory($environment, $this->app);

        // Save environment to config.
        if ($environmentFactory->init($path)) {
            // Load config from file.
            $environmentFactory->getConfig();
            
            if ($environmentFactory->config == NULL) {
                $this->createTerraYml($input, $output, $environmentFactory);
            }
            $environment['document_root'] = isset($environmentFactory->config['document_root']) ? $environmentFactory->config['document_root'] : '';

            // Save current branch
            $environment['version'] = $environmentFactory->getRepo()->getCurrentBranch();

            // Pick a port
            $environment['port'] = $environmentFactory->getAvailablePort();

            // Save to registry.
            $this->getApplication()->getTerra()->getConfig()->saveEnvironment($environment);
            $this->getApplication()->getTerra()->getConfig()->save();

            $output->writeln('<info>Environment saved to registry.</info>');
        } else {
            $output->writeln('<error>Unable to clone repository. Check app settings and try again.</error>');

            return;
        }

        // Offer to enable the environment
        $question = new ConfirmationQuestion("Enable this environment? [y\N] ", false);
        if ($input->getOption('enable') || $helper->ask($input, $output, $question)) {
            // Run environment:enable command.
            $command = $this->getApplication()->find('environment:enable');
            $arguments = array(
              'app_name' => $this->app->name,
              'environment_name' => $environment_name
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);
        }
    }
    
    /**
     * Help the user create their .terra.yml file.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \terra\Factory\EnvironmentFactory                 $environment
     */
    protected function createTerraYml(InputInterface $input, OutputInterface $output, EnvironmentFactory $environment)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('No .terra.yml found. Would you like to create one? [y\N] ', false);

        // If yes, gather the necessary info for creating .terra.yml.
        if ($helper->ask($input, $output, $question)) {
            $question = new Question('Please enter the relative path to your exposed web files: [.] ', '.');
            $document_root = $helper->ask($input, $output, $question);
            $environment->config['document_root'] = $document_root;

            // Create the terra.yml file.
            if($environment->writeTerraYml()) {
                $output->writeln('.terra.yml has been created in the repository root.');
            }
            else{
                $output->writeln('There was an error creating .terra.yml.');
            }
        }
    }
}
