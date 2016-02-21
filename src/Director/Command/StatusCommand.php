<?php

namespace Director\Command;

use Director\DirectorApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
  public $director;

  function __construct(DirectorApplication $director) {
    parent::__construct();
    $this->director = $director;
  }

  protected function configure()
  {
    $this
      ->setName('status')
      ->setDescription('Display the current status.')
      ->addArgument(
        'server',
        InputArgument::OPTIONAL,
        'Which server?'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = $input->getArgument('server');
    if (!$name) {
      $name = 'localhost';
    }
    $output->writeln("Hello World!");
    $output->writeln("Server: " . $this->director->config['server']);

    // SERVERS table.
    $table = $this->getHelper('table');
    $table->setHeaders(array('SERVERS', 'Provider', 'IP', 'Services'));

    $rows = array();
    foreach ($this->director->config['servers'] as $name => $server) {
      $server = (object) $server;
      $ips = !empty($server->ip_addresses)?
        implode(', ', $server->ip_addresses):
        '';
      $services_list = !empty($server->services)?
        implode(', ', $server->services):
        '';
      $row = array(
        $server->hostname,
        $server->provider,
        $ips,
        $services_list,
      );
      $rows[] = $row;
    }
    $table->setRows($rows);
    $table->render($output);


    // APPS table.
    $table = $this->getHelper('table');
    $table->setHeaders(array('APPS', 'Description', 'Repo', 'environments'));

    $rows = array();
    foreach ($this->director->apps as $app) {
      $environments_list = !empty($app->environments)?
        implode(', ', array_keys($app->environments)):
        '';

      if (isset($app->app->source_path)) {
        $source = $app->source_url . "\n" . $app->app->source_path;
      }
      else {
        $source = $app->source_url;
      }

      $row = array(
        $app->name,
        $app->description,
        $source,
        $environments_list,
      );
      $rows[] = $row;
    }
    $table->setRows($rows);
    $table->render($output);

    // SERVICES table.
    $table = $this->getHelper('table');
    $table->setHeaders(array('SERVICES', 'type', 'Galaxy Role', 'Description'));

    $rows = array();
    foreach ($this->director->services as $service) {
      $row = array(
        $service->name,
        $service->type,
        $service->galaxy_role,
        $service->description,
      );
      $rows[] = $row;
    }
    $table->setRows($rows);
    $table->render($output);
  }
}