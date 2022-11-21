<?php

/**
 * Application configuration
 *
 * Not named config.php so as to explicitly indicate that this applies to the
 * entire application, and also that this file will be shown first when listing
 * the files in the config directory in alphabetical order.
 *
 * @return array
 */

return [
    /** @property string version Application version, read from VERSION.txt in image, created by scripts/version.sh. */
    'version' => trim(file_get_contents('VERSION.txt') ?: 'no-version'), // e.g. v0.1.0-develop-1234abc-20221121T0230Z

    /** @property string env_var_prefix Application-specific prefix for names of environment variables in .env. */
    'env_var_prefix' => 'GETMAIL_',

    /** @property string log_tag Log tag used in application logs. */
    'log_tag' => 'GETMAIL',
];