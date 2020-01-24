<?php

namespace Goksagun\RedisOrmBundle\ORM;

class Configuration
{
    /** @var string|array */
    private $hosts;

    /** @var string|array */
    private $paths;

    /** @var array */
    private $options;

    /**
     * Configuration constructor.
     * @param string|array $host
     * @param string|array $paths
     * @param array $options
     */
    public function __construct($host, $paths, array $options = [])
    {
        $this->hosts = $host;
        $this->paths = $paths;
        $this->options = $options;
    }

    /**
     * @return string|array
     */
    public function getHosts()
    {
        return $this->hosts;
    }

    /**
     * @param string|array $hosts
     * @return Configuration
     */
    public function setHosts($hosts): Configuration
    {
        $this->hosts = $hosts;

        return $this;
    }

    /**
     * @return string|array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * @param string|array $paths
     * @return Configuration
     */
    public function setPaths($paths): Configuration
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return Configuration
     */
    public function setOptions(array $options): Configuration
    {
        $this->options = $options;

        return $this;
    }
}