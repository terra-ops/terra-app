<?php

namespace AnsibleWrapper\Event;

/**
 * Static list of events thrown by this library.
 */
final class AnsibleEvents
{
    /**
     * Event thrown prior to executing a ansible command.
     *
     * @var string
     */
    const GIT_PREPARE = 'ansible.command.prepare';

    /**
     * Event thrown when real-time output is returned from the Ansible command.
     *
     * @var string
     */
    const GIT_OUTPUT = 'ansible.command.output';

    /**
     * Event thrown after executing a succesful ansible command.
     *
     * @var string
     */
    const GIT_SUCCESS = 'ansible.command.success';

    /**
     * Event thrown after executing a unsuccesful ansible command.
     *
     * @var string
     */
    const GIT_ERROR = 'ansible.command.error';

    /**
     * Event thrown if the command is flagged to skip execution.
     *
     * @var string
     */
    const GIT_BYPASS = 'ansible.command.bypass';

    /**
     * Deprecated in favor of AnsibleEvents::GIT_PREPARE.
     *
     * @var string
     *
     * @deprecated since version 1.0.0beta5
     */
    const GIT_COMMAND = 'ansible.command.prepare';
}
