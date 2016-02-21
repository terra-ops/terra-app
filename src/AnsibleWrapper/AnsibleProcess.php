<?php

namespace AnsibleWrapper;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

/**
 * AnsibleProcess runs a Ansible command in an independent process.
 */
class AnsibleProcess extends Process
{
    /**
     * @var \AnsibleWrapper\AnsibleWrapper
     */
    protected $ansible;

    /**
     * @var \AnsibleWrapper\AnsibleCommand
     */
    protected $command;

    /**
     * Constructs a AnsibleProcess object.
     *
     * @param \AnsibleWrapper\AnsibleWrapper $ansible
     * @param \AnsibleWrapper\AnsibleCommand $command
     * @param string|null $cwd
     */
    public function __construct(AnsibleWrapper $ansible, AnsibleCommand $command, $cwd = null)
    {
        $this->ansible = $ansible;
        $this->command = $command;

        // Build the command line options, flags, and arguments.
        $binary = ProcessUtils::escapeArgument($ansible->getAnsibleBinary());
        $commandLine = rtrim($binary . ' ' . $command->getCommandLine());

        // Resolve the working directory of the Ansible process. Use the directory
        // in the command object if it exists.
        if (null === $cwd) {
            if (null !== $directory = $command->getDirectory()) {
                if (!$cwd = realpath($directory)) {
                    throw new AnsibleException('Path to working directory could not be resolved: ' . $directory);
                }
            }
        }

        // Finalize the environment variables, an empty array is converted
        // to null which enherits the environment of the PHP process.
        $env = $ansible->getEnvVars();
        if (!$env) {
            $env = null;
        }

        parent::__construct($commandLine, $cwd, $env, null, $ansible->getTimeout(), $ansible->getProcOptions());
    }

    /**
     * {@inheritdoc}
     */
    public function run($callback = null)
    {
        $event = new Event\AnsibleEvent($this->ansible, $this, $this->command);
        $dispatcher = $this->ansible->getDispatcher();

        try {

            // Throw the "ansible.command.prepare" event prior to executing.
            $dispatcher->dispatch(Event\AnsibleEvents::GIT_PREPARE, $event);

            // Execute command if it is not flagged to be bypassed and throw the
            // "ansible.command.success" event, otherwise do not execute the comamnd
            // and throw the "ansible.command.bypass" event.
            if ($this->command->notBypassed()) {
                parent::run($callback);

                if ($this->isSuccessful()) {
                    $dispatcher->dispatch(Event\AnsibleEvents::GIT_SUCCESS, $event);
                } else {
                    $output = $this->getErrorOutput();

                    if(trim($output) == '') {
                        $output = $this->getOutput();
                    }

                    throw new \RuntimeException($output);
                }
            } else {
                $dispatcher->dispatch(Event\AnsibleEvents::GIT_BYPASS, $event);
            }

        } catch (\RuntimeException $e) {
            $dispatcher->dispatch(Event\AnsibleEvents::GIT_ERROR, $event);
            throw new AnsibleException($e->getMessage());
        }
    }
}
