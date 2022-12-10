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
     * Generate unique identifier
     *
     * The result is not meant to be cryptographically secure.
     *
     * @param float $timestampInMicroseconds=0 Timestamp in microseconds to be
     *     used instead of current timestamp, e.g. 1669947544.547348.
     * @return string 42-character string with format
     *     <UNIX timestamp in microseconds>Z-<random 23-character string>,
     *     e.g. 1669950476.198900Z-4b340550242239.64159797.
     */
    public static function generateId(float $timestampInMicroseconds = 0.0): string
    {
        return uniqid(
            // ensure 6-digit microseconds
            str_pad($timestampInMicroseconds ?: microtime(true), 17, '0', STR_PAD_RIGHT) . 'Z-',
            true
        );
    }

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
}
