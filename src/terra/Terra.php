<?php

namespace terra;

/**
 * Class Terra.
 */
class Terra
{
    const NAME = 'Terra CLI';
    const VERSION = '1.x';

    /**
     * Holds Terra configuration settings.
     *
     * @var Config
     */
    private $config;

    /**
     * Getter for Configuration.
     *
     * @return Config
     *                Configuration object.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Setter for Configuration.
     *
     * @param Config $config
     *                       Configuration object.
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
