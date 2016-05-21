<?php

namespace terra\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Command\Command as CommandBase;

use terra\Factory\EnvironmentFactory;

/**
 * Class Command.
 */
class Command extends CommandBase
{
    protected $app;
    protected $environment;

    /**
     * Helper to ask a question only if a default argument is not present.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Question        $question
     *                                  A Question object
     * @param $argument_name
     *   Name of the argument or option to default to.
     * @param string $type
     *                     Either "argument" (default) or "option"
     *
     * @return mixed
     *               The value derived from either the argument/option or the value.
     */
    public function getAnswer(InputInterface $input, OutputInterface $output, Question $question, $argument_name, $type = 'argument', $required = FALSE)
    {
        $helper = $this->getHelper('question');

        if ($type == 'argument') {
            $value = $input->getArgument($argument_name);
        } elseif ($type == 'option') {
            $value = $input->getOption($argument_name);
        }

        if (empty($value)) {

            // If we are in non-interactive mode, we have no choice but to return nothing.
            if ($input->getOption('yes')) {
                return '';
            }
            if ($required) {
                while (empty($value)) {
                    $value = $helper->ask($input, $output, $question);
                }
            }
            else {
                $value = $helper->ask($input, $output, $question);
            }
        }

        return $value;
    }

    /**
     * Gets the application instance for this command.
     *
     * @return \terra\Console\Application
     *
     * @api
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

    /**
     * Helper to ask the user what app they want to work with.
     */
    public function getApp(InputInterface $input, OutputInterface $output)
    {

        // If there are no projects, end command.
        if (count($this->getApplication()->getTerra()->getConfig()->get('projects')) == 0) {
            throw new \Exception('There are no projects to remove!. Use the command <info>terra app:add</info> to add your first app.');
        }

        $helper = $this->getHelper('question');
        $app_name = $input->getArgument('app_name');

        // If no name specified provide options
        if (empty($app_name)) {
          $applications = array_flip(array_keys($this->getApplication()->getTerra()->getConfig()->get('projects')));
          foreach (array_keys($applications) as $app_key) {
              $applications[$app_key] = $app_key;
            }

            $question = new ChoiceQuestion(
                'Which app? ',
                $applications,
                null
            );
            $app_name = $helper->ask($input, $output, $question);
        }

        // If still empty throw an exception.
        if (empty($app_name)) {
            throw new \Exception("App '$app_name' not found.'");
        }
        else {
            // Set the app for this command.
            $this->app = (object) $this->getApplication()->getTerra()->getConfig()->get('projects', $app_name);
        }
    }

    /**
     * Helper to ask the user what app they want to work with.
     */
    public function getEnvironment(InputInterface $input, OutputInterface $output)
    {

        // If no app...
        if (empty($this->app)) {
            throw new \Exception('App not defined. Call Command::getApp() first.');
        }

        // If no environments:
        if (count(($this->app->environments)) == 0) {
            $output->writeln("<comment>There are no environments for the app {$this->app->name}!</comment>");
            $output->writeln('Use the command <info>terra environment:add</info> to add your first environment.');
            return;
        }

        $helper = $this->getHelper('question');
        $environment_name = $input->getArgument('environment_name');

        // If no environment name specified provide options
        if (empty($environment_name)) {
            $environments = array_flip(array_keys($this->app->environments));
            foreach (array_keys($environments) as $env_key) {
              $environments[$env_key] = $env_key;
            }
            $question = new ChoiceQuestion(
                'Which environment? ',
                $environments,
                null
            );
            $environment_name = $helper->ask($input, $output, $question);
        }

        // Set the environment for this command.
        $this->environment = (object) $this->app->environments[$environment_name];
    }

    /**
     * Get an environmentFactory class
     *
     * @return \terra\Factory\EnvironmentFactory
     *
     * @api
     */
    public function getEnvironmentFactory()
    {
      return new EnvironmentFactory($this->environment, $this->app);
    }
}
