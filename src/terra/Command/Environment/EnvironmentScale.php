<?php

namespace terra\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use terra\Command\Command;
use terra\Factory\EnvironmentFactory;

class EnvironmentScale extends Command
{
    protected function configure()
    {
        $this
        ->setName('environment:scale')
        ->setDescription('Scale the app container.')
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
        ->addArgument(
            'scale',
            InputArgument::OPTIONAL,
            'The number of app containers to run.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello Terra!');

        // Ask for an app and environment.
        $this->getApp($input, $output);
        $this->getEnvironment($input, $output);

        $environment_name = $this->environment->name;
        $app_name = $this->app->name;

        $environment_factory = new EnvironmentFactory($this->environment, $this->app);
        $environment_factory->writeConfig();
        $current_scale = $environment_factory->getScale();

        $output->writeln("Scaling Environment <comment>{$app_name} {$environment_name}</comment>...");
        $output->writeln("Current scale: <comment>{$current_scale}</comment>");

        // If no scale ask for scale.
        if (empty($scale)) {
            $question = new Question(
                'How many app containers? '
            );
            $helper = $this->getHelper('question');
            $scale = $helper->ask($input, $output, $question);
        }
        $output->writeln("Target scale: <comment>{$scale}</comment>");

        $environment_factory->scale($scale);

        $output->writeln("Environment <comment>{$app_name} {$environment_name}</comment> scaled to <info>{$scale}</info>");

        // Output the new URL.
        $local_url = 'http://'. $environment_factory->getHost() . ':' . $environment_factory->getPort();
        $output->writeln('<info>Environment enabled!</info>  Available at http://'.$environment_factory->getUrl().' and '.$local_url);

    }
}
