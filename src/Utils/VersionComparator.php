<?php

namespace PhpMigrationAnalyzer\Utils;

/**
 * Version comparison utility
 */
class VersionComparator
{
    /**
     * Compare two version strings
     *
     * @param string $version1
     * @param string $version2
     * @return int Returns -1 if version1 < version2, 0 if equal, 1 if version1 > version2
     */
    public static function compare(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }

    /**
     * Check if version is between two versions
     */
    public static function isBetween(string $version, string $min, string $max): bool
    {
        return self::compare($version, $min) >= 0 && self::compare($version, $max) <= 0;
    }

    /**
     * Check if version is greater than or equal
     */
    public static function isGreaterThanOrEqual(string $version1, string $version2): bool
    {
        return self::compare($version1, $version2) >= 0;
    }

    /**
     * Check if version is less than or equal
     */
    public static function isLessThanOrEqual(string $version1, string $version2): bool
    {
        return self::compare($version1, $version2) <= 0;
    }

    /**
     * Get version range between two versions
     *
     * @param string $from Starting version
     * @param string $to Ending version
     * @param array $availableVersions List of available versions
     * @return array List of versions in the range
     */
    public static function getVersionsInRange(string $from, string $to, array $availableVersions): array
    {
        $versions = [];

        foreach ($availableVersions as $version) {
            if (self::isBetween($version, $from, $to)) {
                $versions[] = $version;
            }
        }

        usort($versions, [self::class, 'compare']);

        return $versions;
    }

    /**
     * Normalize version string (remove prefixes like 'v', 'php')
     */
    public static function normalize(string $version): string
    {
        $version = strtolower(trim($version));
        $version = preg_replace('/^(v|php)/i', '', $version);

        return $version;
    }

    /**
     * Parse PHP version to get major.minor
     */
    public static function getMajorMinor(string $version): string
    {
        $version = self::normalize($version);
        $parts = explode('.', $version);

        if (count($parts) >= 2) {
            return $parts[0] . '.' . $parts[1];
        }

        return $version;
    }

    /**
     * Get next minor version
     */
    public static function getNextMinor(string $version): string
    {
        $version = self::normalize($version);
        $parts = explode('.', $version);

        if (count($parts) >= 2) {
            $minor = (int)$parts[1] + 1;
            return $parts[0] . '.' . $minor;
        }

        return $version;
    }

    /**
     * Get next major version
     */
    public static function getNextMajor(string $version): string
    {
        $version = self::normalize($version);
        $parts = explode('.', $version);

        if (count($parts) >= 1) {
            $major = (int)$parts[0] + 1;
            return $major . '.0';
        }

        return $version;
    }
}
