<?php

namespace App;

use DateTime;
use DateTimeZone;

/**
 * Helper functions
 */
class Helper
{
    /**
     * @var string
     */
    protected static $requestId = '';

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
     * Get request ID of current server request
     *
     * @return string
     */
    public static function getRequestId()
    {
        if (! self::$requestId) { // can't populate during declaration cos no request to refer to
            self::$requestId = self::makeUniqueId($_SERVER['REQUEST_TIME_FLOAT']);
        }

        return self::$requestId;
    }

    /**
     * Generate/make unique identifier
     *
     * The result not meant to be cryptographically secure.
     * Method name uses "make" as "generate" is too long.
     *
     * @param float $timestampInMicroseconds=0 Timestamp in microseconds to be
     *     used instead of current timestamp, e.g. 1669947544.547348.
     * @return string Format: <UNIX timestamp in microseconds>-<UUID v4>,
     *     e.g. 1669950476.198900-c4cbd916-380c-4201-be3e-c1f3d9f6ca69.
     */
    public static function makeUniqueId(float $timestampInMicroseconds = 0.0)
    {
        // Adapted from UUID::v4() in https://www.php.net/manual/en/function.uniqid.php#94959
        // See also uuid_generate_random() in https://github.com/symfony/polyfill-uuid/blob/main/Uuid.php
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        return (
            str_pad($timestampInMicroseconds ?: microtime(true), 17, '0', STR_PAD_RIGHT) // ensure 6-digit microseconds
            . '-'
            . $uuid
        );
    }
}
