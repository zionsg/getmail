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
    protected $config = [
        /** @property string env_var_prefix Application-specific prefix for names of environment variables. */
        'env_var_prefix' => 'GETMAIL_',

        /** @property string log_tag Log tag used in application logs. */
        'log_tag' => 'GETMAIL',
    ];

    /**
     * Get value of configuration key
     *
     * $config is checked first, followed by environment variables.
     *
     * @param string $configKey Configuration key, typically in snake_case.
     * @param mixed $default=null Default value to return if key is not found.
     * @return mixed
     */
    public function get($configKey, $default = null)
    {
        $key = trim(strval($configKey));
        if (!$key) {
            return $default;
        }

        // Cannot use ?? with $default cos key may exist with value of null
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        $value = getenv($this->resolveEnvVar($key));
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
    public function getCurrentTimestamp($returnAsString = false)
    {
        $utcDate = new DateTime('now', new DateTimeZone('UTC')); // always in UTC timezone

        return ($returnAsString ? $utcDate->format('Y-m-d\TH:i:s.up') : $utcDate);
    }

    /**
     * Get deployment environment
     *
     * @return string
     */
    public function getDeploymentEnvironment()
    {
        return (getenv($this->resolveEnvVar('env')) ?: 'none'); // e.g. from APP_ENV env var
    }

    /**
     * Get application version
     *
     * @return string
     */
    public function getVersion()
    {
        return (getenv($this->resolveEnvVar('version')) ?: 'none'); // e.g. from APP_VERSION env var
    }

    /**
     * Resolve name of environment variable with prefix
     *
     * @param string $name
     * @return string
     */
    protected function resolveEnvVar($name)
    {
        // Name of env var is always in uppercase
        return ($this->config['env_var_prefix'] ?? '') . strtoupper($name);
    }
}
