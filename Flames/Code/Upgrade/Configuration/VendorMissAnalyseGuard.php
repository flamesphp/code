<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configuration;

use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Skipper\FileSystem\PathNormalizer;
final class VendorMissAnalyseGuard
{
    /**
     * @param string[] $configuredPaths
     */
    public function isVendorAnalyzed(array $configuredPaths): bool
    {
        if ($this->hasDowngradeSets()) {
            return \false;
        }
        return $this->isComposerVendorRootConfigured($configuredPaths);
    }
    private function hasDowngradeSets(): bool
    {
        /** @var string[] $registeredRectorSets */
        $registeredRectorSets = SimpleParameterProvider::provideArrayParameter(\Flames\Code\Upgrade\Configuration\Option::REGISTERED_RECTOR_SETS);
        $found = \false;
        foreach ($registeredRectorSets as $registeredRectorSet) {
            if (str_contains($registeredRectorSet, 'downgrade-')) {
                $found = \true;
                break;
            }
        }
        return $found;
    }
    /**
     * Warn only when the configured path is the project Composer vendor root,
     * not when upgrading an explicit package such as vendor/flamesphp/transpiler.
     *
     * @param string[] $configuredPaths
     */
    private function isComposerVendorRootConfigured(array $configuredPaths): bool
    {
        $vendorRoot = realpath(getcwd() . '/vendor');
        if ($vendorRoot === \false) {
            return \false;
        }
        $normalizedVendorRoot = PathNormalizer::normalize($vendorRoot);
        foreach ($configuredPaths as $configuredPath) {
            $realPath = realpath($configuredPath);
            if ($realPath === \false) {
                continue;
            }
            if (PathNormalizer::normalize($realPath) === $normalizedVendorRoot) {
                return \true;
            }
        }
        return \false;
    }
}
