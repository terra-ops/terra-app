<?php

namespace AnsibleWrapper\Event;

/**
 * Interface implemented by output listeners.
 */
interface AnsibleOutputListenerInterface
{
    public function handleOutput(AnsibleOutputEvent $event);
}
