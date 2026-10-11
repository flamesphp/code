<?php

declare (strict_types=1);
namespace PHPStan\Php;

use PHPStan\DependencyInjection\AutowiredService;
use function explode;
use function max;
use function min;
use const PHP_VERSION_ID;
#[AutowiredService(\null, '@PHPStan\Php\PhpVersionFactoryFactory::create')]
final class PhpVersionFactory
{
    private ?int $versionId;
    private ?string $composerPhpVersion;
    public const MIN_PHP_VERSION = 70100;
    public const MAX_PHP_VERSION = 80699;
    public const MAX_PHP5_VERSION = 50699;
    public const MAX_PHP7_VERSION = 70499;
    public function __construct(?int $versionId, ?string $composerPhpVersion)
    {
        $this->versionId = $versionId;
        $this->composerPhpVersion = $composerPhpVersion;
    }
    public function create(): \PHPStan\Php\PhpVersion
    {
        $versionId = $this->versionId;
        if ($versionId !== null) {
            $source = \PHPStan\Php\PhpVersion::SOURCE_CONFIG;
        } elseif ($this->composerPhpVersion !== null) {
            $parts = explode('.', $this->composerPhpVersion);
            $tmp = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
            $tmp = max($tmp, self::MIN_PHP_VERSION);
            $versionId = min($tmp, self::MAX_PHP_VERSION);
            $source = \PHPStan\Php\PhpVersion::SOURCE_COMPOSER_PLATFORM_PHP;
        } else {
            $versionId = PHP_VERSION_ID;
            $source = \PHPStan\Php\PhpVersion::SOURCE_RUNTIME;
        }
        return new \PHPStan\Php\PhpVersion($versionId, $source);
    }
}
