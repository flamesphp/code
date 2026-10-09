<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\VersionBonding\Contract;

use Flames\Code\Upgrade\ValueObject\PhpVersion;
/**
 * Can be implemented by @see \Flames\Code\Upgrade\Contract\Rector\RectorInterface
 *
 * Rules that do not meet this PHP version will be skipped.
 */
interface MinPhpVersionInterface
{
    /**
     * @return PhpVersion::*
     */
    public function provideMinPhpVersion(): int;
}
