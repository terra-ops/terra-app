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
    protected $project;
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
     * Helper to ask the user what project they want to work with.
     */
    public function getProject(InputInterface $input, OutputInterface $output)
    {

        // If there are no projects, end command.
        if (count($this->getApplication()->getTerra()->getConfig()->get('projects')) == 0) {
            throw new \Exception('There are no projects to remove!. Use the command <info>terra project:add</info> to add your first project.');
        }

        $helper = $this->getHelper('question');
        $project_name = $input->getArgument('project_name');

        // If no name specified provide options
        if (empty($project_name)) {
          $projects = array_flip(array_keys($this->getApplication()->getTerra()->getConfig()->get('projects')));
          foreach (array_keys($projects) as $project_key) {
              $projects[$project_key] = $project_key;
            }

            $question = new ChoiceQuestion(
                'Which project? ',
                $projects,
                null
            );
            $project_name = $helper->ask($input, $output, $question);
        }

        // If still empty throw an exception.
        if (empty($project_name)) {
            throw new \Exception("Project '$project_name' not found.'");
        }
        else {
            // Set the project for this command.
            $this->project = (object) $this->getApplication()->getTerra()->getConfig()->get('projects', $project_name);
        }
    }

    /**
     * Helper to ask the user what project they want to work with.
     */
    public function getEnvironment(InputInterface $input, OutputInterface $output)
    {

        // If no project...
        if (empty($this->project)) {
            throw new \Exception('Project not defined. Call Command::getProject() first.');
        }

        // If no environments:
        if (count(($this->project->environments)) == 0) {
            $output->writeln("<comment>There are no environments for the project {$this->project->name}!</comment>");
            $output->writeln('Use the command <info>terra environment:add</info> to add your first environment.');
            return;
        }

        $helper = $this->getHelper('question');
        $environment_name = $input->getArgument('environment_name');

        // If no environment name specified provide options
        if (empty($environment_name)) {
            $environments = array_flip(array_keys($this->project->environments));
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
        $this->environment = (object) $this->project->environments[$environment_name];
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
      return new EnvironmentFactory($this->environment, $this->project);
    }
}
