<?php

namespace App;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Utility functions
 */
class Utils
{
    /**
     * @var string
     */
    protected static $requestId = '';

    /**
     * Get current timestamp in UTC timezone
     *
     * @param boolean $returnAsString=false Whether to return as string.
     *     If true, timestamp is returned in ISO 8601 format with microseconds.
     * @return DateTimeImmutable|string
     */
    public static function utcNow(bool $returnAsString = false): DateTimeImmutable|string
    {
        $utcDate = new DateTimeImmutable('now', new DateTimeZone('UTC')); // always in UTC timezone

        return ($returnAsString ? $utcDate->format('Y-m-d\TH:i:s.up') : $utcDate);
    }

    /**
     * Get request ID of current server request
     *
     * @return string See makeUniqueId() on format.
     */
    public static function getRequestId(): string
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
     * Method name uses "make" instead of "generate" for brevity.
     *
     * @param float $timestampInMicroseconds=0 Timestamp in microseconds to be
     *     used instead of current timestamp, e.g. 1669947544.547348.
     * @return string Format: <UNIX timestamp in microseconds>-<UUID v4>,
     *     e.g. 1669950476.198900-c4cbd916-380c-4201-be3e-c1f3d9f6ca69.
     */
    public static function makeUniqueId(float $timestampInMicroseconds = 0.0): string
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
