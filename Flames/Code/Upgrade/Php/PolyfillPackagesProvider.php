<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Php;

use FlamesPrefix202610\Nette\Utils\FileSystem;
use FlamesPrefix202610\Nette\Utils\Json;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\ValueObject\PolyfillPackage;
final class PolyfillPackagesProvider
{
    /**
     * @var null|array<int, PolyfillPackage::*>
     */
    private $cachedPolyfillPackages = null;
    /**
     * @return array<int, PolyfillPackage::*>
     */
    public function provide(): array
    {
        // disable cache in tests
        if (SimpleParameterProvider::hasParameter(Option::POLYFILL_PACKAGES)) {
            return SimpleParameterProvider::provideArrayParameter(Option::POLYFILL_PACKAGES);
        }
        // already cached, even only empty array
        if ($this->cachedPolyfillPackages !== null) {
            return $this->cachedPolyfillPackages;
        }
        $projectComposerJson = getcwd() . '/composer.json';
        if (!file_exists($projectComposerJson)) {
            $this->cachedPolyfillPackages = [];
            return $this->cachedPolyfillPackages;
        }
        $composerContents = FileSystem::read($projectComposerJson);
        $composerJson = Json::decode($composerContents, \true);
        $this->cachedPolyfillPackages = $this->filterPolyfillPackages($composerJson['require'] ?? []);
        return $this->cachedPolyfillPackages;
    }
    /**
     * @param array<string, string> $require
     * @return array<int, PolyfillPackage::*>
     */
    private function filterPolyfillPackages(array $require): array
    {
        return array_filter(array_keys($require), static fn(string $packageName): bool => str_starts_with($packageName, 'symfony/polyfill-'));
    }
}
