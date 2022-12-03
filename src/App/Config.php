<?php

namespace App;

use DateTime;
use DateTimeZone;

/**
 * Centralized configuration class
 *
 * This is the 1st class to be loaded, hence it should not depend on any other
 * classes, e.g. it should not use App\Logger to log messages.
 */
class Config
{
    /**
     * Application configuration
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Environment variable prefix
     *
     * @var string
     */
    protected static $envVarPrefix = '';

    /**
     * Application name
     *
     * @var string
     */
    protected static $applicationName = '';

    /**
     * Deployment environment
     *
     * @var string
     */
    protected static $deploymentEnvironment = '';

    /**
     * Application version
     *
     * @var string
     */
    protected static $version = '';

    /**
     * Initialize static class - this must be called right at the start
     *
     * This merges all the PHP files in $configPath in alphabetical order,
     * ignoring subdirectories, ideally with application.config.php being the
     * first and zenith.local.php being the last (if it exists).
     *
     * @param string $configPath Absolute path to directory containing
     *     configuration files.
     * @return void
     */
    public static function init(string $configPath)
    {
        foreach (glob("{$configPath}/*.php") as $configFile) {
            self::$config = array_merge(
                self::$config,
                (include $configFile) ?: []
            );
        }

        // Save commonly used config vars
        self::$envVarPrefix = self::get('env_var_prefix'); // this must be first as resolveEnvVar() depends on it
        self::$applicationName = self::get('app_name');
        self::$deploymentEnvironment = self::get('env');
        self::$version = self::get('version');
    }

    /**
     * Get value of configuration key
     *
     * Application config is checked first, followed by environment variables.
     *
     * @param string $configKey Configuration key, typically in snake_case.
     * @param mixed $default=null Default value to return if key is not found.
     * @return mixed
     */
    public static function get(string $configKey, mixed $default = null)
    {
        $key = trim(strval($configKey));
        if (! $key) {
            return $default;
        }

        // Check application config
        // Cannot use ?? with $default cos key may exist with value of null
        if (array_key_exists($key, self::$config)) {
            return self::$config[$key];
        }

        // Check environment variables
        $value = getenv(self::resolveEnvVar($key));
        if (false === $value) { // env var not found
            return $default;
        }

        // Note that values of env vars are always strings
        return $value;
    }

    /**
     * Get application name
     *
     * @return string
     */
    public static function getApplicationName()
    {
        return self::$applicationName;
    }

    /**
     * Get deployment environment
     *
     * Not named getEnvironment() to avoid confusion with environment variables.
     *
     * @return string
     */
    public static function getDeploymentEnvironment()
    {
        return self::$deploymentEnvironment;
    }

    /**
     * Get application version
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }

    /**
     * Resolve name of environment variable with prefix
     *
     * @param string $name
     * @return string
     */
    protected static function resolveEnvVar(string $name)
    {
        // Name of env var is always in uppercase
        return (self::$envVarPrefix . strtoupper($name));
    }
}
