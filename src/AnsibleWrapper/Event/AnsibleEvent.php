<?php

namespace AnsibleWrapper\Event;

use AnsibleWrapper\AnsibleCommand;
use AnsibleWrapper\AnsibleWrapper;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Process;

/**
 * Event instance passed as a result of ansible.* commands.
 */
class AnsibleEvent extends Event
{
    /**
     * The AnsibleWrapper object that likely instantiated this class.
     *
     * @var \AnsibleWrapper\AnsibleWrapper
     */
    protected $wrapper;

    /**
     * The Process object being run.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * The AnsibleCommand object being executed.
     *
     * @var \AnsibleWrapper\AnsibleCommand
     */
    protected $command;

    /**
     * Constructs a AnsibleEvent object.
     *
     * @param \AnsibleWrapper\AnsibleWrapper $wrapper
     *   The AnsibleWrapper object that likely instantiated this class.
     * @param \Symfony\Component\Process\Process $process
     *   The Process object being run.
     * @param \AnsibleWrapper\AnsibleCommand $command
     *   The AnsibleCommand object being executed.
     */
    public function __construct(AnsibleWrapper $wrapper, Process $process, AnsibleCommand $command)
    {
        $this->wrapper = $wrapper;
        $this->process = $process;
        $this->command = $command;
    }

    /**
     * Gets the AnsibleWrapper object that likely instantiated this class.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function getWrapper()
    {
        return $this->wrapper;
    }

    /**
     * Gets the Process object being run.
     *
     * @return \Symfony\Component\Process\Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * Gets the AnsibleCommand object being executed.
     *
     * @return \AnsibleWrapper\AnsibleCommand
     */
    public function getCommand()
    {
        return $this->command;
    }
}
