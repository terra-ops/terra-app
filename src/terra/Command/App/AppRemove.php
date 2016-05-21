<?php

namespace terra\Command\App;

use terra\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

class AppRemove extends Command
{
    protected function configure()
    {
        $this
        ->setName('app:remove')
        ->setDescription('Removes an app.')
        ->addArgument(
            'app_name',
            InputArgument::OPTIONAL,
            'The name the app to remove.'
        )
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $helper = $this->getHelper('question');
        $this->getApp($input, $output);
        $name = $this->app->name;

        // Confirm removal of the app.
        if (!$input->hasOption('no-interaction')) {
            $question = new ConfirmationQuestion("Are you sure you would like to remove the app <question>$name</question>? [y/N] ", false);
        }
        else {
            $output->writeln("<warning>Running with --no-interaction. Skipping confirmation step.</warning>");
        }       
        if (!$input->hasOption('no-interaction') && !$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Cancelled</error>');

            return;
        } else {
            $this->getApplication()->getTerra()->getConfig()->remove('projects', $name);
            $this->getApplication()->getTerra()->getConfig()->save();
            $output->writeln("<info>App $name has been removed.</info>");

            // @TODO: Remove all environments and files associated with this app.
        }
    }
}
