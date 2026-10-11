<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Php\PhpVersion;
use function str_contains;
use function str_replace;
final class SkipPolyfillSourceLocator implements SourceLocator
{
    private SourceLocator $sourceLocator;
    private PhpVersion $phpVersion;
    public function __construct(SourceLocator $sourceLocator, PhpVersion $phpVersion)
    {
        $this->sourceLocator = $sourceLocator;
        $this->phpVersion = $phpVersion;
    }
    #[Override]
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
    {
        $reflection = $this->sourceLocator->locateIdentifier($reflector, $identifier);
        if ($reflection === null) {
            return null;
        }
        if ($reflection instanceof ReflectionClass || $reflection instanceof ReflectionFunction || $reflection instanceof ReflectionConstant) {
            $fileName = $reflection->getFileName();
            if ($fileName !== null) {
                $normalized = str_replace('\\', '/', $fileName);
                if (str_contains($normalized, '/symfony/polyfill-php80/') && $this->phpVersion->versionId >= 80000) {
                    return null;
                }
                if (str_contains($normalized, '/symfony/polyfill-php81/') && $this->phpVersion->versionId >= 80100) {
                    return null;
                }
                if (str_contains($normalized, '/symfony/polyfill-php82/') && $this->phpVersion->versionId >= 80200) {
                    return null;
                }
                if (str_contains($normalized, '/symfony/polyfill-php83/') && $this->phpVersion->versionId >= 80300) {
                    return null;
                }
                if (str_contains($normalized, '/symfony/polyfill-php84/') && $this->phpVersion->versionId >= 80400) {
                    return null;
                }
                if (str_contains($normalized, '/symfony/polyfill-php85/') && $this->phpVersion->versionId >= 80500) {
                    return null;
                }
            }
        }
        return $reflection;
    }
    #[Override]
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
