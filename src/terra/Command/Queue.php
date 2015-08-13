<?php

namespace terra\Command;

use terra\Factory\EnvironmentFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Queue extends Command
{
    protected function configure()
    {
        $this
            ->setName('queue')
            ->setDescription('Run all the commands in the Queue')
            ->addArgument(
                'url',
                InputArgument::OPTIONAL,
                'The AMPQ server URL in the format of "username:password@server:port/queue',
                'tcp://guest:guest@local.computer:5672/terra'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $server = parse_url($url);

        // AMQP server information
        $server['queue'] = substr($server['path'], 1);
        $output->writeln("Checking <question>$url</question> ...");

        $connection = new AMQPConnection($server['host'], $server['port'], $server['user'], $server['pass']);
        $channel = $connection->channel();
        $channel->queue_declare('hello', false, false, false, false);

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            $cmds = (array)json_decode($msg->body);
            $args = array_shift($cmds);

            // Convert array into command string
            $cmd = $args->cmd;
            $return = array(
                'args' => json_encode($args),
                'cmd' => json_encode($cmd),
                'output' => array(),
                'status' => FALSE,
            );
            if (is_object($cmd)) {
                $flags = array();
                foreach($cmd->flags as $key => $value) {
                    $flags[] = ($value === TRUE) ? $key : $key .'='. $value;
                }
                $cmd = $cmd->cmd . ' ' . implode(' ', $cmd->args) . ' ' . implode(' ', $flags);
            }

            // Execute command
            echo ' [x] Executing ' . $cmd;
            $result = exec($cmd, $return['output'], $return['status']);
            $return['result'] = $result;
            $return['output'] = implode("\r\n", $return['output']);
            echo print_r($return, 1);

            // Queue up additional commands if necessary
            if (!empty($cmds)) {
                global $server;
                $connection1 = new AMQPConnection($server['host'], $server['port'], $server['user'], $server['pass']);
                $channel1 = $connection1->channel();
                $channel1->queue_declare('hello', false, false, false, false);

                $data = json_encode($cmds);
                $msg = new AMQPMessage($data);
                $channel1->basic_publish($msg, '', $server['queue']);
                echo " [x] Posted leftover commands \n";

                $channel1->close();
                $connection1->close();
            }

            // POST results to callback
            // @todo: Make guzzle work
            /*if (!empty($args->callback)) {
              $client = new GuzzleHttp\Client();
              try {
                $res = $client->get($args->callback, $return);
                echo " [x] Called $args->callback: status ". $res->getStatusCode() ." \n";
                echo $res->getBody();
              }
              catch(Exception $e) {
                echo ' [ ] Problem calling callback url: ' .$e->getMessage();
                if ($response = $e->getResponse()) {
                  echo $response->getBody();
                }
              }
            }*/
            // Guzzle isn't working for some reason, use curl instead
            $curl = curl_init($args->callback);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $return);
            $curl_response = curl_exec($curl);
            if ($curl_response === false) {
                $info = curl_getinfo($curl);
                echo ' [ ] Problem calling callback url: ' .var_export($info);
            }
            else {
                $decoded = json_decode($curl_response);
                echo " [x] Called $args->callback: ". $curl_response ." \n";
            }
            curl_close($curl);

        };

$channel->basic_consume($server['queue'], '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

    }
}