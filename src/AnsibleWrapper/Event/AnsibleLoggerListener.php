<?php

namespace AnsibleWrapper\Event;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnsibleLoggerListener implements EventSubscriberInterface, LoggerAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Mapping of event to log level.
     *
     * @var array
     */
    protected $logLevelMappings = array(
        AnsibleEvents::GIT_PREPARE => LogLevel::INFO,
        AnsibleEvents::GIT_OUTPUT  => LogLevel::DEBUG,
        AnsibleEvents::GIT_SUCCESS => LogLevel::INFO,
        AnsibleEvents::GIT_ERROR   => LogLevel::ERROR,
        AnsibleEvents::GIT_BYPASS  => LogLevel::INFO,
    );

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the log level mapping for an event.
     *
     * @param string $eventName
     * @param string|false $logLevel
     *
     * @return \AnsibleWrapper\Event\AnsibleLoggerListener
     */
    public function setLogLevelMapping($eventName, $logLevel)
    {
        $this->logLevelMappings[$eventName] = $logLevel;
        return $this;
    }

    /**
     * Returns the log level mapping for an event.
     *
     * @param string $eventName
     *
     * @return string
     *
     * @throws \DomainException
     */
    public function getLogLevelMapping($eventName)
    {
        if (!isset($this->logLevelMappings[$eventName])) {
            throw new \DomainException('Unknown event: ' . $eventName);
        }

        return $this->logLevelMappings[$eventName];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            AnsibleEvents::GIT_PREPARE => array('onPrepare', 0),
            AnsibleEvents::GIT_OUTPUT  => array('handleOutput', 0),
            AnsibleEvents::GIT_SUCCESS => array('onSuccess', 0),
            AnsibleEvents::GIT_ERROR   => array('onError', 0),
            AnsibleEvents::GIT_BYPASS  => array('onBypass', 0),
        );
    }

    /**
     * Adds a logg message using the level defined in the mappings.
     *
     * @param \AnsibleWrapper\Event\AnsibleEvent $event
     * @param string $message
     * @param array $context
     *
     * @throws \DomainException
     */
    public function log(AnsibleEvent $event, $message, array $context = array())
    {
        $method = $this->getLogLevelMapping($event->getName());
        if ($method !== false) {
            $context += array('command' => $event->getProcess()->getCommandLine());
            $this->logger->$method($message, $context);
        }
    }

    public function onPrepare(AnsibleEvent $event)
    {
        $this->log($event, 'Ansible command preparing to run');
    }

    public function handleOutput(AnsibleOutputEvent $event)
    {
        $context = array('error' => $event->isError() ? true : false);
        $this->log($event, $event->getBuffer(), $context);
    }

    public function onSuccess(AnsibleEvent $event)
    {
        $this->log($event, 'Ansible command successfully run');
    }

    public function onError(AnsibleEvent $event)
    {
        $this->log($event, 'Error running Ansible command');
    }

    public function onBypass(AnsibleEvent $event)
    {
        $this->log($event, 'Ansible command bypassed');
    }
}
