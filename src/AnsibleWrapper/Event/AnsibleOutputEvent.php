<?php

namespace AnsibleWrapper\Event;

use AnsibleWrapper\AnsibleCommand;
use AnsibleWrapper\AnsibleWrapper;
use Symfony\Component\Process\Process;

/**
 * Event instance passed when output is returned from Ansible commands.
 */
class AnsibleOutputEvent extends AnsibleEvent
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $buffer;

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
    public function __construct(AnsibleWrapper $wrapper, Process $process, AnsibleCommand $command, $type, $buffer)
    {
        parent::__construct($wrapper, $process, $command);
        $this->type = $type;
        $this->buffer = $buffer;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Tests wheter the buffer was captured from STDERR.
     */
    public function isError()
    {
        return (Process::ERR == $this->type);
    }
}
