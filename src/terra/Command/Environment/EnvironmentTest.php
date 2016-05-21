<?php

namespace terra\Command\Environment;

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

class EnvironmentTest extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:test')
        ->setDescription('Run tests on the environment.')
        ->addArgument(
            'app_name',
            InputArgument::OPTIONAL,
            'The name of the project.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name of the environment.'
        )
        ->addOption(
            'name',
            NULL,
            InputOption::VALUE_OPTIONAL
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello Terra!');

        // Ask for an project and environment.
        $this->getApp($input, $output);
        $this->getEnvironment($input, $output);

        $environment_factory = new EnvironmentFactory($this->environment, $this->project);

        $environment_factory->getConfig();
        if (isset($environment_factory->config['hooks']['test'])) {
            $this->executeTests($input, $output);
            return;
        }

        if ($environment_factory->config['behat_path']) {
            $this->executeBehatTests($input, $output);
            return;
        }

        if (empty($environment_factory->config['behat_path'])) {
            $this->prepareBehat($input, $output, $environment_factory);
        }
    }

    protected function executeTests(InputInterface $input, OutputInterface $output) {
        // Run the tests
        // @TODO: Move to factory.

        $environment_factory = new EnvironmentFactory($this->environment, $this->project);

        $output->writeln('<info>TERRA</info> | <comment>Test: Start...</comment>');
        $output->writeln('<info>TERRA</info> | ' . $environment_factory->config['hooks']['test']);

        // Set environment variables for behat tests
        $env = array();
        $env['HOME'] = $_SERVER['HOME'];
//        $behat_vars = array(
//            'extensions' => array(
//                'Behat\\MinkExtension' => array(
//                    'base_url' => 'http://'.$environment_factory->getUrl(),
//                ),
//                'Drupal\\DrupalExtension' => array(
//                    'drush' => array(
//                        'alias' => $environment_factory->getDrushAlias(),
//                    ),
//                    'drupal' => array(
//                        'drupal_root' => $environment_factory->getDocumentRoot(),
//                    ),
//                ),
//            ),
//        );

        // @TODO: This is NOT WORKING.  We MUST figure out how to override the base_url.
//        $env['BEHAT_PARAMS'] = json_encode($behat_vars);

        $process = new Process($environment_factory->config['hooks']['test'], $environment_factory->getSourcePath(), $env);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo $buffer;
            } else {
                echo $buffer;
            }
        });

        if (!$process->isSuccessful()) {
            $output->writeln('<info>TERRA</info> | <fg=red>Test Failed</> '.$hook);
        } else {
            $output->writeln('<info>TERRA</info> | <info>Test Passed: </info> '.$hook);
        }
        $output->writeln('');
    }

    /**
     * Using the config item "behat_path", run composer update and bin/behat in the behat path.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeBehatTests(InputInterface $input, OutputInterface $output) {
        $output->writeln('Running Behat Tests...');
        $environment_factory = new EnvironmentFactory($this->environment, $this->project);
        $environment_factory->getConfig();

        // 1. Look for behat.yml
        $behat_path = $this->environment->path . '/' . $environment_factory->config['behat_path'];
        $behat_yml_path = $behat_path . '/behat.yml';
        if (!file_exists($behat_path)) {
            throw new \Exception("Path $behat_path not found. Check your project's .terra.yml file.");
        }
        elseif (!file_exists($behat_yml_path)) {
            throw new \Exception("Behat.yml file not found at $behat_yml_path. Check your project's .terra.yml file.");
        }
        $output->writeln('Found behat.yml file at ' . $behat_yml_path);

        // 2. Load it, replace necessary items, and clone it to a temporary file.
        $behat_yml =  Yaml::parse(file_get_contents($behat_yml_path));

        // Set Base URL
        $behat_yml['default']['extensions']['Behat\\MinkExtension']['base_url'] = "http://{$environment_factory->getHost()}:{$environment_factory->getPort()}";
        $behat_yml['default']['extensions']['Drupal\\DrupalExtension']['drush']['alias'] = $environment_factory->getDrushAlias();

        // If driver is drupal, add root.
        if ($behat_yml['default']['extensions']['Drupal\\DrupalExtension']['api_driver'] == 'drupal') {
            $behat_yml['default']['extensions']['Drupal\\DrupalExtension']['drupal']['root'] = $environment_factory->getDocumentRoot();
        }

        $behat_yml_new = Yaml::dump($behat_yml, 5, 2);
        $behat_path_new = 'behat.terra.yml';
        $fs = new Filesystem();
        $fs->dumpFile($behat_path_new, $behat_yml_new);

        $output->writeln('Generated new behat.yml file at ' . $behat_path_new);

        // 3. Run `composer install` in behat_path.
        $output->writeln('');
        $output->writeln('<fg=cyan>TERRA</> | <comment>Running: composer install</comment>');

        $process = new Process('composer install', $behat_path);
        $process->setTimeout(NULL);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo $buffer;
            } else {
                echo $buffer;
            }
        });

        $output->writeln('');
        $output->writeln('<fg=cyan>TERRA</> | <comment>Behat Tests: Start</comment>');

        // 4. Run `bin/behat --colors --config=$PATH` in behat_path.
        // "expand:true" expands scenario outlines, making them readable.

        $cmd = 'bin/behat --colors --format-settings=\'{"expand": true}\' --config=' . $behat_path_new;
        if ($input->getOption('name')) {
            $cmd .= ' --name=' . $input->getOption('name');
        }

        $output->writeln("Running: $cmd");
        $output->writeln("in: $behat_path");
        $output->writeln('');

        $process = new Process($cmd, $behat_path);
        $process->setTimeout(NULL);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo $buffer;
            } else {
                echo $buffer;
            }
        });
        $output->writeln('');

        if (!$process->isSuccessful()) {
            $output->writeln('<fg=cyan>TERRA</> | <fg=red>Test Failed</> ');
            return 1;
        } else {
            $output->writeln('<fg=cyan>TERRA</> | <info>Test Passed!</info> ');
            return 0;
        }
    }

    protected function prepareBehat(InputInterface $input, OutputInterface $output, EnvironmentFactory $environment_factory) {
        $output->writeln("I noticed you don't have behat tests for your project.");

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Would you like to add behat tests? [y\N] ');
        if ($helper->ask($input, $output, $question)) {
            $question = new Question('Where would you like to add your tests? (tests) ', 'tests');
            $tests_path = $helper->ask($input, $output, $question);

            // Create tests path
            $fs = new Filesystem();
            try {
                $fs->mkdir($environment_factory->getSourcePath() . '/' . $tests_path);
            }
            catch (IOException $e) {
                throw \Exception($e->getMessage());
            }
            $output->writeln("<info>SUCCESS</info> Created $tests_path.");

            // Create composer.json and behat.yml
            $composer_path = $environment_factory->getSourcePath() . '/' . $tests_path . '/composer.json';
            $behat_yml_path = $environment_factory->getSourcePath() . '/' . $tests_path . '/behat.yml';

            try {
                $fs->dumpFile($composer_path, $this->getBehatDrupalComposer());
                $url = "http://{$environment_factory->getHost()}:{$environment_factory->getPort()}";
                $fs->dumpFile($behat_yml_path, $this->getBehatYml($url));
                $output->writeln("<info>SUCCESS</info> Created composer.json and behat.yml.");
            }
            catch (IOException $e) {
                throw \Exception($e->getMessage());
            }

            $behat_path = $this->environment->path . '/' . $tests_path;

            // Run composer install
            $output->writeln("<info>RUNNING</info> composer install");
            $output->writeln("in: $behat_path");
            $process = new Process('composer install', $behat_path);
            $process->setTimeout(NULL);
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo $buffer;
                } else {
                    echo $buffer;
                }
            });
            $output->writeln("");

            // Run behat --init
            $output->writeln("<info>RUNNING</info> bin/behat --init");
            $output->writeln("in: $behat_path");
            $process = new Process('bin/behat --init', $behat_path);
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo $buffer;
                } else {
                    echo $buffer;
                }
            });

            // Set behat_path in .terra.yml
            $output->writeln("<comment>NOTE You should add `behat_path: $tests_path` to .terra.yml.</>");
        }
    }

    protected function getBehatDrupalComposer() {
        return json_encode(array(
            'require' => array(
                'drupal/drupal-extension' => '~3.0',
            ),
            'config' => array(
                'bin-dir' => 'bin/',
            ),
        ));
    }
    protected function getBehatYml($url) {
        return <<<YML
default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Drupal\DrupalExtension\Context\DrupalContext
        - Drupal\DrupalExtension\Context\MinkContext
        - Drupal\DrupalExtension\Context\MessageContext
        - Drupal\DrupalExtension\Context\DrushContext
  extensions:
    Behat\MinkExtension:
      goutte: ~
      selenium2: ~
      base_url: $url
    Drupal\DrupalExtension:
      blackbox: ~
YML;

    }
}
