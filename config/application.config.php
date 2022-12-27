<?php

/**
 * Application configuration
 *
 * Named application.config.php and not config.php so as to explicitly indicate
 * that this applies to the entire application, and that it will ideally be the
 * first file to be read when merging the files in the config directory in
 * alphabetical order.
 *
 * As per The Twelve-Factor App, config should be set via environment variables.
 * This file is mainly for reading in the env vars and putting it into a native
 * PHP array with sane defaults. For clarity and ease of search/replace, env
 * vars are spelt out fully instead of interpolating the common vendor prefix,
 * i.e. getenv('VENDOR_COMPONENT_VARIABLE') instead of
 * getenv("{$vendorPrefix}COMPONENT_VARIABLE"). Note that values of env vars
 * are always strings, hence defaults are of string type.
 *
 * @return array
 */

use Psr\Log\LogLevel;

return [
    /** @property string version Application version, from VERSION.txt in Docker image created by scripts/version.sh. */
    'version' => trim(file_get_contents('VERSION.txt') ?: 'no-version'), // e.g. v0.1.0-develop-1234abc-20221121T0230Z

    /** @property string deployment_environment Deployment environment: production/staging/feature/testing/local. */
    'deployment_environment' => getenv('GETMAIL_ENV') ?: 'production',

    /** @property string log_level Log level to cap logs at, e.g. INFO logs will not be output if ERROR is set here. */
    'log_level' => getenv('GETMAIL_LOG_LEVEL') ?: LogLevel::DEBUG,

    /** @property string app_name Application name. */
    'app_name' => getenv('GETMAIL_APP_NAME') ?: 'APP',

    /** @property array router Router configuration. Will be replaced by config/router.config.php. */
    'router' => [],

    /** @property string api_key API key. */
    'api_key' => getenv('GETMAIL_API_KEY') ?: 'none',

    /** @property string api_key API key. */
    'api_token' => getenv('GETMAIL_API_TOKEN') ?: 'none',

    /** @property string layout_path Absolute path to layout template for Web responses. */
    'web_layout_path' => getcwd() . DIRECTORY_SEPARATOR . 'src/Web/view/layout.phtml',

    /** @property string mail_username Username for mail account. */
    'mail_username' => getenv('GETMAIL_MAIL_USERNAME') ?: 'none',

    /** @property string mail_password Password for mail account. */
    'mail_password' => getenv('GETMAIL_MAIL_PASSWORD') ?: 'none',

    /** @property string mail_imap_host IMAP host for mail account. */
    'mail_imap_host' => getenv('GETMAIL_MAIL_IMAP_HOST') ?: 'imap.example.com',

    /** @property string mail_username Username for mail account. */
    'mail_imap_port' => getenv('GETMAIL_MAIL_IMAP_PORT') ?: '993',
];
