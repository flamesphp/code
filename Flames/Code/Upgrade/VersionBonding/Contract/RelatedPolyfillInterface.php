<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\VersionBonding\Contract;

use Flames\Code\Upgrade\ValueObject\PolyfillPackage;
/**
 * Can be implemented by @see \Flames\Code\Upgrade\Contract\Rector\RectorInterface
 */
interface RelatedPolyfillInterface
{
    /**
     * @return PolyfillPackage::*
     */
    public function providePolyfillPackage(): string;
}
