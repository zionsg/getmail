<?php

namespace App;

use DateTime;
use DateTimeZone;

/**
 * Centralized configuration class
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
     * Deployment environment
     *
     * @var string
     */
    protected static $deploymentEnvironment = '';

    /**
     * Environment variable prefix
     *
     * @var string
     */
    protected static $envVarPrefix = '';

    /**
     * Application version
     *
     * @var string
     */
    protected static $version = '';

    /**
     * Initialize static class - this must be called right at the start
     *
     * This looks specifically for application.config.php and local.php if it
     * exists. Any other config files should be loaded by these 2 files.
     *
     * @param string $configPath Absolute path to directory containing
     *     configuration files.
     * @return void
     */
    public static function init(string $configPath)
    {
        self::$config = array_merge(
            self::$config ?: [],
            include "{$configPath}/application.config.php" ?: [],
            file_exists("{$configPath}/local.php") ? (include "{$configPath}/local.php" ?: []) : []
        );

        // Save commonly used config vars
        self::$deploymentEnvironment = self::get('env');
        self::$envVarPrefix = self::get('env_var_prefix');
        self::$version = self::get('version');

        // Save version as environment variable so that it appears when `printenv` is run in terminal
        putenv(self::resolveEnvVar('version') . '=' . self::$version);
    }

    /**
     * Get value of configuration key
     *
     * $config is checked first, followed by environment variables.
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

        // Cannot use ?? with $default cos key may exist with value of null
        if (array_key_exists($key, self::$config)) {
            return self::$config[$key];
        }

        $value = getenv(self::resolveEnvVar($key));
        if (false === $value) { // env var not found
            return $default;
        }

        // Note that values of env vars are always strings
        return $value;
    }

    /**
     * Get current timestamp
     *
     * @param boolean $returnAsString=false Whether to return as string.
     *     If true, timestamp is returned in ISO 8601 format with microseconds.
     * @return DateTime|string Timestamp will always be in UTC timezone.
     */
    public static function getCurrentTimestamp(bool $returnAsString = false)
    {
        $utcDate = new DateTime('now', new DateTimeZone('UTC')); // always in UTC timezone

        return ($returnAsString ? $utcDate->format('Y-m-d\TH:i:s.up') : $utcDate);
    }

    /**
     * Get deployment environment
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
