<?php

declare (strict_types=1);
namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\ComposerHelper;
use function count;
use function end;
use function is_array;
use function is_int;
use function is_string;
#[AutowiredService]
final class PhpVersionFactoryFactory
{
    /**
     * @var int|array{min: int, max: int}|null
     */
    private $phpVersion;
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    /**
     * @param int|array{min: int, max: int}|null $phpVersion
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(
        #[AutowiredParameter]
        $phpVersion,
        #[AutowiredParameter]
        array $composerAutoloaderProjectPaths
    )
    {
        $this->phpVersion = $phpVersion;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
    }
    public function create(): \PHPStan\Php\PhpVersionFactory
    {
        $composerPhpVersion = null;
        if (count($this->composerAutoloaderProjectPaths) > 0) {
            $composerJsonPath = end($this->composerAutoloaderProjectPaths);
            $composer = ComposerHelper::getComposerConfig($composerJsonPath);
            if ($composer !== null) {
                $platformVersion = $composer['config']['platform']['php'] ?? null;
                if (is_string($platformVersion)) {
                    $composerPhpVersion = $platformVersion;
                }
            }
        }
        $versionId = null;
        if (is_int($this->phpVersion)) {
            $versionId = $this->phpVersion;
        }
        if (is_array($this->phpVersion)) {
            $versionId = $this->phpVersion['min'];
        }
        return new \PHPStan\Php\PhpVersionFactory($versionId, $composerPhpVersion);
    }
}
