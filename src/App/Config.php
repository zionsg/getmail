<?php

namespace App;

/**
 * Application configuration class
 */
class Config
{
    /**
     * Merged application configuration array
     *
     * @var array
     */
    protected $config = [];

    /**
     * Application name
     *
     * @var string
     */
    protected $applicationName = '';

    /**
     * Deployment environment
     *
     * @var string
     */
    protected $deploymentEnvironment = '';

    /**
     * Application version
     *
     * @var string
     */
    protected $version = '';

    /**
     * Constructor
     *
     * This merges all the PHP files in $configPath in alphabetical order,
     * ignoring subdirectories, ideally with application.config.php being the
     * first and zenith.local.php being the last (if it exists).
     *
     * @param string $configPath Absolute path to directory containing
     *     configuration files.
     * @return void
     */
    public function __construct(string $configPath)
    {
        foreach (glob("{$configPath}/*.php") as $configFile) {
            $this->config = array_merge(
                $this->config,
                (include $configFile) ?: []
            );
        }

        // Save frequently used config vars
        $this->applicationName = $this->get('app_name');
        $this->deploymentEnvironment = $this->get('deployment_environment');
        $this->version = $this->get('version');
    }

    /**
     * Get value of configuration key
     *
     * @param string $configKey Configuration key, typically in snake_case.
     * @param mixed $default=null Default value to return if key is not found.
     * @return mixed
     */
    public function get(string $configKey, mixed $default = null)
    {
        $key = trim(strval($configKey));
        if (! $key) {
            return $default;
        }

        // Cannot use ?? with $default as key may exist with value of null
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Get application name
     *
     * @return string
     */
    public function getApplicationName(): string
    {
        return $this->applicationName;
    }

    /**
     * Get deployment environment
     *
     * Not named getEnvironment() to avoid confusion with environment variables.
     *
     * @return string
     */
    public function getDeploymentEnvironment(): string
    {
        return $this->deploymentEnvironment;
    }

    /**
     * Get application version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
