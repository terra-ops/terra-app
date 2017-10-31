<?php

namespace terra\Command;

use terra\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

class PrepareSystem extends Command
{
    protected function configure()
    {
        $this
        ->setName('prepare:system')
        ->setDescription('Builds app containers for your system and launches URL Proxy, among other things.')
        ->addArgument(
            'uid',
            InputArgument::OPTIONAL,
            'The UID to use when generating the container users. Defaults to the current user.'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $output->writeln([
        "First, I need a couple of things:",
        "  1. A web container built to match your user, so volume mounts match your control user.",
        "  2. A local URL proxy (jwilder/nginx-proxy), so requests to local URLs can resolve to individual containers.",
        ""
      ]);

      if (!$input->getArgument('uid')) {
        $uid = trim(shell_exec('id -u'));
        if (empty($uid) || !intval($uid)) {
          throw new \Exception("UID not found. The command `id -u` failed and returned `$uid`. Please pass your user's UID as an argument to this command: `terra prepare:system 1100`");
        }
        $output->writeln([
          "It looks like your UID is $uid.",
        ]);
      }
      else {
        $uid = $input->getArgument('uid');
      }

      // Build app containers.
      $path_to_drupal_docker = realpath(__DIR__ . '/../../../docker/drupal');
      $cmd = "docker build -t terra/drupal:local --build-arg TERRA_UID={$uid} {$path_to_drupal_docker}";

      $output->writeln([
        "I'd like to run `$cmd`  ..."
      ]);

      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion("Ok? [Y/n]", false);

      // If yes, gather the necessary info for creating .terra.yml.
      if ($helper->ask($input, $output, $question)) {

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
          if (Process::ERR === $type) {
            echo 'DOCKER > '.$buffer;
          } else {
            echo 'DOCKER > '.$buffer;
          }
        });

        if ($process->isSuccessful()) {

          $output->writeln([
            '',
            "<info>Ok! Your Docker host now has an image for terra/drupal:local</info>",
            "I'll use this image to launch your Drupal environments.",
            '',
          ]);
      }
      else {

        $output->writeln([
          "",
          "<fg=red>Uh oh! The `docker-build` command failed!</>",
          "The command I tried to run was: ",
          "<comment>{$cmd}</comment>",
          "",
          "Please check your settings, try to run the command manually, then try again."
        ]);

        exit(1);
      }
      }
      
      $path_to_drupal_docker = realpath(__DIR__ . '/../../../docker/drush');
      $cmd = "docker build -t terra/drush:local {$path_to_drupal_docker}";
  
      $output->writeln([
        "I'd like to run `$cmd`  ..."
      ]);
  
      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion("Ok? [Y/n]", false);
  
      // If yes, gather the necessary info for creating .terra.yml.
      if ($helper->ask($input, $output, $question)) {
    
        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
          if (Process::ERR === $type) {
            echo 'DOCKER > '.$buffer;
          } else {
            echo 'DOCKER > '.$buffer;
          }
        });
    
        if ($process->isSuccessful()) {
      
          $output->writeln([
            '',
            "<info>Ok! Your Docker host now has an image for terra/drupal:local</info>",
            "I'll use this image to launch your Drupal environments.",
            '',
          ]);
        }
        else {
      
          $output->writeln([
            "",
            "<fg=red>Uh oh! The `docker-build` command failed!</>",
            "The command I tried to run was: ",
            "<comment>{$cmd}</comment>",
            "",
            "Please check your settings, try to run the command manually, then try again."
          ]);
      
          exit(1);
        }
      }
     
      $output->writeln([
        "",
        "Next up: URL Proxy. I need to launch a container with jwilder/nging-proxy for this to work.",
        "You can review the Docker image here: https://hub.docker.com/r/jwilder/nginx-proxy/",
        "",
      ]);

      $process = new Process('docker inspect terra-nginx-proxy');
      $process->setTimeout(null);
      $process->run();
      if ($process->isSuccessful()) {

        $output->writeln([
          "",
          "Wait a minute, it looks like you already have a container 'terra-nginx-proxy'. You should be good to go.",
        ]);
      }
      else {
        $cmd = 'docker run --name terra-nginx-proxy -d -p 80:80 -v /var/run/docker.sock:/tmp/docker.sock:ro --security-opt label:disable jwilder/nginx-proxy';
  
        $output->writeln([
          "I'd like to run `$cmd`  "
        ]);
  
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Ok?  [Y/n]", false);
  
        // If yes, gather the necessary info for creating .terra.yml.
        if ($helper->ask($input, $output, $question)) {
    
          $process = new Process($cmd);
          $process->setTimeout(null);
          $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
              echo 'DOCKER > '.$buffer;
            } else {
              echo 'DOCKER > '.$buffer;
            }
          });
    
          if ($process->isSuccessful()) {
      
            $output->writeln([
              "",
              "<info>Ok, that worked! Now you have a container bound to port 80 on your host.</info>",
              "",
            ]);
          }
          else {
            throw new \Exception("Ouch. Something went wrong when running the command. Review and try again.");
          }
        }
      }
      
      // ADD NETWORK
      $output->writeln([
        "",
        "Last step: For the URL Proxy to work, we need to add a docker network called 'terra-nginx-network'.",
        "",
      ]);
  
      $process = new Process('docker network inspect terra-nginx-network');
      $process->setTimeout(NULL);
      $process->run();
      if ($process->isSuccessful()) {
    
        $output->writeln([
          "",
          "Wait a minute, it looks like you already have a network called 'terra-nginx-network'.",
        ]);
      }
      else {
    
        $cmds = [];
        $cmds[] = 'docker network create terra-nginx-network';
    
        $output->writeln([
          "I'd like to run:"
        ]);
        $output->writeln($cmds);
    
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Ok?  [Y/n]", FALSE);
        if ($helper->ask($input, $output, $question)) {
      
          foreach ($cmds as $cmd) {
            $process = new Process($cmd);
            $process->setTimeout(NULL);
            $process->run(function ($type, $buffer) {
              if (Process::ERR === $type) {
                echo 'DOCKER > ' . $buffer;
              }
              else {
                echo 'DOCKER > ' . $buffer;
              }
            });
  
            if ($process->isSuccessful()) {
              $output->writeln([
                "<info>Great! The network was created.</info>",
              ]);
            }
            else {
              throw new \Exception("Ouch. Something went wrong when running the command. Review and try again.");
            }
          }
      
        }
      }
      
      // Attach containers
      $cmd = 'docker network connect terra-nginx-network terra-nginx-proxy';
  
      $output->writeln([
        "I'd like to run: " . $cmd
      ]);

      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion("Ok?  [Y/n]", FALSE);
      if ($helper->ask($input, $output, $question)) {
 
        $process = new Process($cmd);
        $process->setTimeout(NULL);
        $process->run(function ($type, $buffer) {
          if (Process::ERR === $type) {
            echo 'DOCKER > ' . $buffer;
          }
          else {
            echo 'DOCKER > ' . $buffer;
          }
        });
      }
  
      $output->writeln([
        "",
        "You should be good to go! Try `terra app:add` to get started!",
      ]);
    }
}
