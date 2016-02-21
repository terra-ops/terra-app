<?php

namespace AnsibleWrapper\Event;

/**
 * Event handler that streams real-time output from Ansible commands to STDOUT and
 * STDERR.
 */
class AnsibleOutputStreamListener implements AnsibleOutputListenerInterface
{
    public function handleOutput(AnsibleOutputEvent $event)
    {
        $handler = $event->isError() ? STDERR : STDOUT;
        fputs($handler, $event->getBuffer());
    }
}
