<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\VersionBonding;

use Flames\Code\Upgrade\ThirdParty\Composer\Semver;
use Flames\Code\Upgrade\ThirdParty\Composer\InstalledPackageResolver;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
/**
 * @see \Flames\Code\Upgrade\Tests\VersionBonding\ComposerPackageConstraintFilterTest
 */
final readonly class ComposerPackageConstraintFilter
{
    public function __construct(private InstalledPackageResolver $installedPackageResolver)
    {
    }
    /**
     * @param list<RectorInterface> $rectors
     * @return list<RectorInterface>
     */
    public function filter(array $rectors): array
    {
        $activeRectors = [];
        foreach ($rectors as $rector) {
            if (!$rector instanceof ComposerPackageConstraintInterface) {
                $activeRectors[] = $rector;
                continue;
            }
            if ($this->satisfiesComposerPackageConstraint($rector)) {
                $activeRectors[] = $rector;
            }
        }
        return $activeRectors;
    }
    private function satisfiesComposerPackageConstraint(ComposerPackageConstraintInterface $rector): bool
    {
        $composerPackageConstraint = $rector->provideComposerPackageConstraint();
        $composerPackageConstraints = is_array($composerPackageConstraint) ? $composerPackageConstraint : [$composerPackageConstraint];
        // every constraint must be satisfied, so the rule fits every required package at once
        foreach ($composerPackageConstraints as $composerPackageConstraint) {
            $packageVersion = $this->installedPackageResolver->resolvePackageVersion($composerPackageConstraint->getPackageName());
            if ($packageVersion === null) {
                return \false;
            }
            if (!Semver::satisfies($packageVersion, $composerPackageConstraint->getConstraint())) {
                return \false;
            }
        }
        return \true;
    }
}
