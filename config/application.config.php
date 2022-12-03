<?php

/**
 * Application configuration
 *
 * Named application.config.php and not config.php so as to explicitly indicate
 * that this applies to the entire application, and that it will ideally be the
 * first file to be read when merging the files in the config directory in
 * alphabetical order.
 *
 * @return array
 */

use App\Logger;

return [
    /** @property string version Application version, read from VERSION.txt in image, created by scripts/version.sh. */
    'version' => trim(file_get_contents('VERSION.txt') ?: 'no-version'), // e.g. v0.1.0-develop-1234abc-20221121T0230Z

    /** @property string env_var_prefix Vendor prefix for names of environment variables in .env. */
    'env_var_prefix' => 'GETMAIL_',

    /** @property string log_level Log level to cap logs at, e.g. INFO logs will not be output if ERROR is set here. */
    'log_level' => Logger::DEBUG,

    /** @property string layout_path Absolute path to layout template for Web responses. */
    'web_layout_path' => getcwd() . DIRECTORY_SEPARATOR . 'src/Web/view/layout.phtml',
];
