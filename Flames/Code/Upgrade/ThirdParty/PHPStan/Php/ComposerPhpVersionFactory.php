<?php

declare (strict_types=1);
namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\ComposerHelper;
use function count;
use function end;
use function is_string;
use function min;
#[AutowiredService]
final class ComposerPhpVersionFactory
{
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    private ?\PHPStan\Php\PhpVersion $minVersion = null;
    private ?\PHPStan\Php\PhpVersion $maxVersion = null;
    private bool $initialized = \false;
    /**
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(
        #[AutowiredParameter]
        array $composerAutoloaderProjectPaths
    )
    {
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
    }
    private function initializeVersions(): void
    {
        $this->initialized = \true;
        // don't limit minVersion... PHPStan can analyze even PHP5
        $this->maxVersion = new \PHPStan\Php\PhpVersion(\PHPStan\Php\PhpVersionFactory::MAX_PHP_VERSION);
        // fallback to composer.json based php-version constraint
        $composerPhpVersion = $this->getComposerRequireVersion();
        if ($composerPhpVersion === null) {
            return;
        }
        $parser = new \PHPStan\Php\ComposerPhpVersionParser();
        [$minVersion, $maxVersion] = $parser->parse($composerPhpVersion, static function (string $version, int $versionId, bool $isMaxVersion): \PHPStan\Php\PhpVersion {
            if ($isMaxVersion && $version === '6.0.0.0-dev') {
                $versionId = min($versionId, \PHPStan\Php\PhpVersionFactory::MAX_PHP5_VERSION);
            } elseif ($isMaxVersion && $version === '8.0.0.0-dev') {
                $versionId = min($versionId, \PHPStan\Php\PhpVersionFactory::MAX_PHP7_VERSION);
            } else {
                $versionId = min($versionId, \PHPStan\Php\PhpVersionFactory::MAX_PHP_VERSION);
            }
            return new \PHPStan\Php\PhpVersion($versionId);
        });
        if ($minVersion !== null) {
            $this->minVersion = new \PHPStan\Php\PhpVersion($minVersion->versionId);
        }
        if ($maxVersion === null) {
            return;
        }
        $this->maxVersion = $maxVersion;
    }
    public function getMinVersion(): ?\PHPStan\Php\PhpVersion
    {
        if ($this->initialized === \false) {
            $this->initializeVersions();
        }
        return $this->minVersion;
    }
    public function getMaxVersion(): ?\PHPStan\Php\PhpVersion
    {
        if ($this->initialized === \false) {
            $this->initializeVersions();
        }
        return $this->maxVersion;
    }
    private function getComposerRequireVersion(): ?string
    {
        $composerPhpVersion = null;
        if (count($this->composerAutoloaderProjectPaths) > 0) {
            $composer = ComposerHelper::getComposerConfig(end($this->composerAutoloaderProjectPaths));
            if ($composer !== null) {
                $requiredVersion = $composer['require']['php'] ?? null;
                if (is_string($requiredVersion)) {
                    $composerPhpVersion = $requiredVersion;
                }
            }
        }
        return $composerPhpVersion;
    }
}
