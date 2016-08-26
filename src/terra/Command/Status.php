<?php

namespace terra\Command;

use terra\Factory\EnvironmentFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Status extends Command
{
    protected function configure()
    {
        $this
        ->setName('status')
        ->setDescription('Display the current status of the system, a machine, an app, or environment.')
        ->addArgument(
            'app_name',
            InputArgument::OPTIONAL,
            'The name the app to check the status of.'
        )
        ->addArgument(
            'environment_name',
            InputArgument::OPTIONAL,
            'The name the environment to check the status of.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello Terra!');

        $app_name = $input->getArgument('app_name');
        $environment_name = $input->getArgument('environment_name');

        // Show system status
        if (empty($app_name) && empty($environment_name)) {
            $this->systemStatus($input, $output);
        } // Show an app's status
        elseif (empty($environment_name)) {
            $this->appStatus($input, $output);
        } // Show an environment's status.
        elseif (!empty($environment_name)) {
            $this->environmentStatus($input, $output);
        }
    }

    /**
     * Output the overall system status.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function systemStatus(InputInterface $input, OutputInterface $output)
    {
        // If no apps, trigger app:Add command.
        $helper = $this->getHelper('question');
        $apps = $this->getApplication()->getTerra()->getConfig()->get('apps');
        if (empty($apps)) {
            $output->writeln('You have no apps!');
            $question = new ConfirmationQuestion("Add an App? [y\N] ", false);
            if ($helper->ask($input, $output, $question)) {
                // Run environment:add command.
                $command = $this->getApplication()->find('app:add');
                $command->run($input, $output);
                return;
            }
        }

        // APPS table.
        $table = $this->getHelper('table');
        $table->setHeaders(array(
        'APPS',
        'Description',
        'Repo',
        'Environments',
        ));

        $rows = array();
        $options = array();
        foreach ($this->getApplication()
               ->getTerra()
               ->getConfig()
               ->get('apps') as $app) {

            $options[] = $app['name'];
            $row = array(
            $app['name'],
            $app['description'],
            $app['repo'],
            is_array($app['environments']) ? implode(', ', array_keys($app['environments'])) : 'None',
            );
            $rows[] = $row;
        }
        $table->setRows($rows);
        $table->render($output);

        $helper = $this->getHelper('question');
        $question = new Question('App? ');
        $question->setAutocompleterValues($options);
//
//        // Run app status
//        $name = $helper->ask($input, $output, $question);
//        if (empty($name)) {
//            return;
//        }
//        else {
//            // If an app name was chosen, run appStatus
//            $formatter = $this->getHelper('formatter');
//            $input->setArgument('app_name', $name);
//            $this->appStatus($input, $output);
//
//        }
    }

    /**
     * Outputs the status of an app.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $app
     */
    protected function appStatus(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>App:</info> ' . $input->getArgument('app_name'));
      // If there are no apps, return
        if (count($this->getApplication()->getTerra()->getConfig()->get('apps')) == 0) {
            $output->writeln('<comment>There are no apps!</comment>');
            $output->writeln('Use the command <info>terra app:add</info> to add your first app.');

            return;
        }

        $app_name = strtr($input->getArgument('app_name'), array(
        '-' => '_',
        ));

        $app = $this->getApplication()->getTerra()->getConfig()->get('apps', $app_name);

        if (empty($app)) {
            $output->writeln('<error>No app with that name! </error>');

            return 1;
        }

        // If no environments:
        if (count(($app['environments'])) == 0) {
            $output->writeln('<comment>There are no environments!</comment>');
            $output->writeln('Use the command <info>terra environment:add</info> to add your first environment.');

            return;
        }

        $table = $this->getHelper('table');
        $table->setHeaders(array(
          'Name',
          'Code Path / docroot',
          'URLs & Drush Alias',
          'Version',
        ));

        $rows = array();

        foreach ($app['environments'] as $environment) {
            // @TODO: Detect if URL proxy is online
            $environment_factory = new EnvironmentFactory($environment, $app);

            // Build list of domains.
            $environment['domains'][] = 'http://'. $environment_factory->getHost() . ':' . $environment_factory->getPort();
            $environment['domains'][] = 'http://'.$environment_factory->getUrl();

            $environment['url'] = implode(PHP_EOL, $environment['domains']);
            $environment['url'] .= PHP_EOL . $environment_factory->getDrushAlias();

            unset($environment['domains']);

            $rows[] = array(
                $environment['name'],
                $environment['path'] . PHP_EOL . $environment['document_root'],
                $environment['url'],
                $environment['version'],
            );
        }

        $table->setRows($rows);
        $table->render($output);
    }

    /**
     * Outputs the status of an environment.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $app
     * @param $environment
     *
     */
    protected function environmentStatus(InputInterface $input, OutputInterface $output)
    {

        // If there are no apps, return
        if (count($this->getApplication()->getTerra()->getConfig()->get('apps')) == 0) {
            $output->writeln('<comment>There are no apps!</comment>');
            $output->writeln('Use the command <info>terra app:add</info> to add your first app.');

            return;
        }

        $app_name = $input->getArgument('app_name');
        $environment_name = $input->getArgument('environment_name');

        $app = $this->getApplication()->getTerra()->getConfig()->get('apps', $app_name);

        // If no environments:
        if (count(($app['environments'])) == 0) {
            $output->writeln('<comment>There are no environments!</comment>');
            $output->writeln('Use the command <info>terra environment:add</info> to add your first environment.');

            return;
        }

        // If no environment by that name...
        if (!isset($app['environments'][$environment_name])) {
            $output->writeln("<error>There is no environment named {$environment_name} in the app {$app_name}</error>");

            return;
        }

        $environment = $app['environments'][$environment_name];
        $environment_factory = new EnvironmentFactory($environment, $app);

        $environment['scale'] = $environment_factory->getScale();
        $environment['url'] = 'http://'. $environment_factory->getHost() . ':' . $environment_factory->getPort();
        $environment['url'] .= PHP_EOL.'http://'.$environment_factory->getUrl();

        $table = $this->getHelper('table');
        $table->setHeaders(array(
          'Name',
          'Code Path',
          'docroot',
          'URLs',
          'Version',
          'Scale',
        ));

        $rows = array(
          $environment
        );
        $table->setRows($rows);
        $table->render($output);

        $output->writeln('Docker Compose Path: '.$environment_factory->getDockerComposePath());
    }
}
