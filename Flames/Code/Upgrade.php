<?php

declare(strict_types=1);

namespace Flames\Code;

use Flames\Code\Exception\UpgradeFailedException;
use Flames\Code\Upgrade\Programmatic\Runner;
use Flames\Collection\Arr;

/**
 * Programmatic API for Flames code upgrades (no CLI binary required).
 *
 * Usage:
 *   Upgrade::upgrade('8.5');                    // upgrade App/
 *   Upgrade::upgrade('8.5', 'App/Server');    // upgrade a subdirectory
 *   Upgrade::previewUpgrade('8.5');           // dry-run preview as Arr
 */
class Upgrade
{
    /**
     * Upgrade source code to the given PHP version level.
     *
     * @param string $version PHP target, e.g. "8.5", "85", "7.4"
     * @param string $path    Directory relative to project root (default: App/)
     *
     * @throws UpgradeFailedException When the upgrade run reports system errors
     * @throws \InvalidArgumentException When version or path is invalid
     * @throws \RuntimeException When bootstrap or config setup fails
     */
    public static function upgrade(string $version = '8.5', string $path = 'App', bool $clearCache = false): void
    {
        (new Runner())->upgrade($version, $path, $clearCache);
    }

    /**
     * Preview upgrade changes without writing files.
     *
     * Returns an Arr with keys: totals, file_diffs, changed_files, errors (when present).
     *
     * @param string $version PHP target, e.g. "8.5", "85", "7.4"
     * @param string $path    Directory relative to project root (default: App/)
     */
    public static function previewUpgrade(string $version = '8.5', string $path = 'App'): Arr
    {
        return new Arr((new Runner())->preview($version, $path));
    }
}
