<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\VersionBonding;

use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\Php\PolyfillPackagesProvider;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\RelatedPolyfillInterface;
/**
 * @see \Flames\Code\Upgrade\Tests\VersionBonding\PhpVersionedFilterTest
 */
final readonly class PhpVersionedFilter
{
    public function __construct(private PhpVersionProvider $phpVersionProvider, private PolyfillPackagesProvider $polyfillPackagesProvider)
    {
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter(array $rectors): array
    {
        $minProjectPhpVersion = $this->phpVersionProvider->provide();
        $ceilingPhpVersion = $this->resolveCeilingPhpVersion();
        $activeRectors = [];
        foreach ($rectors as $rector) {
            // polyfill package can raise the rule above the project PHP version,
            // but never above an explicitly picked withPhpSets() version
            if ($rector instanceof RelatedPolyfillInterface && $ceilingPhpVersion === null) {
                $polyfillPackageNames = $this->polyfillPackagesProvider->provide();
                if (in_array($rector->providePolyfillPackage(), $polyfillPackageNames, \true)) {
                    $activeRectors[] = $rector;
                    continue;
                }
            }
            if (!$rector instanceof MinPhpVersionInterface) {
                $activeRectors[] = $rector;
                continue;
            }
            // an explicitly picked withPhpSets() version caps the whole set:
            // polyfilled rules up to the ceiling, the rest up to the lower of ceiling and project version
            if ($ceilingPhpVersion !== null) {
                $maxPhpVersion = $rector instanceof RelatedPolyfillInterface ? $ceilingPhpVersion : min($ceilingPhpVersion, $minProjectPhpVersion);
            } else {
                $maxPhpVersion = $minProjectPhpVersion;
            }
            // does satisfy version? → include
            if ($rector->provideMinPhpVersion() <= $maxPhpVersion) {
                $activeRectors[] = $rector;
            }
        }
        return $activeRectors;
    }
    private function resolveCeilingPhpVersion(): ?int
    {
        if (!SimpleParameterProvider::hasParameter(Option::POLYFILL_CEILING_PHP_VERSION)) {
            return null;
        }
        $ceilingPhpVersion = SimpleParameterProvider::provideIntParameter(Option::POLYFILL_CEILING_PHP_VERSION);
        if ($ceilingPhpVersion <= 0) {
            return null;
        }
        return $ceilingPhpVersion;
    }
}
