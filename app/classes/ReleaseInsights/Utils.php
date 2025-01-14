<?php

declare(strict_types=1);

namespace ReleaseInsights;

use Cache\Cache;
use DateTime;

class Utils
{
    /**
     * Get the list of crashes for a Build ID from Socorro
     *
     * @param int $buildid Firefox build ID
     *
     * @return array a list of crashes
     */
    public static function getCrashesForBuildID(int $buildid): array
    {
        // The date in the string varies so we create a unique file name in cache
        $cache_id = 'https://crash-stats.mozilla.com/api/SuperSearch/?build_id=' . $buildid . '&_facets=signature&product=Firefox';

        // If we can't retrieve cached data, we create and cache it.
        // We cache because we want to avoid http request latency
        if (! $data = Cache::getKey($cache_id, 30)) {
            $data = file_get_contents($cache_id);

            // No data returned, bug or incorrect date, don't cache.
            if (empty($data)) {
                return [];
            }
            Cache::setKey($cache_id, $data);
        }

        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Get a date provided by the user in the query string fallback to today's date
     *
     * @return string Date as a Ymd string
     */
    public static function getDate(string $format = 'Ymd'): string
    {
        // No date provided by the http call, return Today
        if (! isset($_GET['date'])) {
            return date($format);
        }

        // Magical 'today' value
        if ($_GET['date'] === 'today') {
            return date($format);
        }

        // Cast user provided date to an int for security
        $date = Utils::secureText($_GET['date']);


        $d = DateTime::createFromFormat($format, $date);

        // Date is invalid, return Today
        if (! $d) {
            return date($format);
        }

        return $d->format($format);
    }

    /**
     * Get a Firefox BuildID and sanitize it
     *
     * @param string $buildid Firefox Build ID in format 20191014213051
     *
     * @return string sanitized buildID
     */
    public static function getBuildID(string $buildid): string
    {
        // Check that the string provided is correct
        if (! self::isBuildID($buildid)) {
            return '20191014213051'; // hardcoded fallback value
        }

        return self::secureText($buildid);
    }

    public static function isBuildID(string $buildid): bool
    {
        //  BuildIDs should be 14 digits
        if (strlen($buildid) !== 14) {
            return false;
        }

        //  BuildIDs should be valid dates, if we can't create a date return false
        if (! $date = date_create($buildid)) {
            return false;
        }

        // The date shouldn't be in the future
        $date  = new DateTime($buildid);
        $today = new DateTime();

        if ($date > $today) {
            return false;
        }

        return true;
    }
    /**
     * Sanitize a string for security before template use.
     *
     * @param string $string The string we want to sanitize
     *
     * @return string Sanitized string for security
     */
    public static function secureText(string $string): string
    {
        // CRLF XSS
        $string = str_replace(['%0D', '%0A'], '', $string);
        // We want to convert line breaks into spaces
        $string = str_replace("\n", ' ', $string);
        // Escape HTML tags and remove ASCII characters below 32
        return filter_var(
            $string,
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_STRIP_LOW
        );
    }

    public static function getJson(string $url, int $ttl = 0): array
    {
        if (! $data = Cache::getKey($url, $ttl)) {
            $data = file_get_contents($url);

            // No data returned, bug or incorrect date, don't cache.
            if (empty($data)) {
                return [];
            }
            Cache::setKey($url, $data);
        }

        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function mtrim(string $string): string
    {
        $string = explode(' ', $string);
        $string = array_filter($string);

        return implode(' ', $string);
    }

    /**
     * Check if $haystack starts with a string in $needles.
     * $needles can be a string or an array of strings.
     *
     * @param string $haystack String to analyse
     * @param string|array  $needles  The string to look for
     *
     * @return bool True if the $haystack string starts with a string in $needles
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if $needles are in $haystack.
     *
     * @param string $haystack  String to analyze
     * @param mixed  $needles   The string (or array of strings) to look for
     * @param bool   $match_all True if we need to match all $needles, false
     *                          if it's enough to match one. Default: false
     *
     * @return bool True if the $haystack string contains any/all $needles
     */
    public static function inString(string $haystack, mixed $needles, bool $match_all = false): bool
    {
        $matches = 0;
        foreach ((array) $needles as $needle) {
            if (str_contains($haystack, $needle)) {
                // If I need to match any needle, I can stop at the first match
                if (! $match_all) {
                    return true;
                }
                $matches++;
            }
        }

        if (! $match_all) {
            return false;
        }

        return $matches === (is_countable($needles) ? count($needles) : 0);
    }

    /**
     * Utility function to get symfony dump() function output to the CLI
     * http://symfony.com/doc/current/components/var_dumper/
     */
    public static function dump(): void
    {
        if (! class_exists(\Symfony\Component\VarDumper\Dumper\CliDumper::class)) {
            return;
        }

        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();
        foreach (func_get_args() as $arg) {
            $dumper->dump($cloner->cloneVar($arg));
        }
    }

    /**
     * @param DateTime $date Date that is to be checked if it falls between $startDate and $endDate
     * @param DateTime $startDate Date should be after this date to return true
     * @param DateTime $endDate Date should be before this date to return true
     */
    public static function isDateBetweenDates(DateTime $date, DateTime $startDate, DateTime $endDate): bool
    {
        return $date > $startDate && $date < $endDate;
    }

    /**
     * Get the major version number (91) from a string such as 91.0.1
     */
    public static function getMajorVersion(string $version): ?int
    {
        if ($version === null) {
            return null;
        }

        return (int) explode('.', $version)[0];
    }
}
