<?php

namespace Flames\Code\Upgrade\ThirdParty\olvlvl\ComposerAttributeCollector;

use Flames\Composer\Composer\IO\IOInterface;
/**
 * @internal
 */
interface Filter
{
    /**
     * @param class-string $class
     */
    public function filter(string $filepath, string $class, IOInterface $io): bool;
}
