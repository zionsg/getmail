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
        self::$deploymentEnvironment = self::get('env');
        self::$version = self::get('version');

        // Save version as environment variable so that it appears when `printenv` is run in terminal
        putenv(self::resolveEnvVar('version') . '=' . self::$version);
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
