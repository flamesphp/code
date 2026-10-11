<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
#[AutowiredService]
final class ExtDsStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private ExtensionVersionProvider $extensionVersionProvider;
    public function __construct(ExtensionVersionProvider $extensionVersionProvider)
    {
        $this->extensionVersionProvider = $extensionVersionProvider;
    }
    public function getFiles(): array
    {
        $version = $this->extensionVersionProvider->getExtensionVersions()['ds'] ?? null;
        if ($version !== null && $version !== 1) {
            return [];
        }
        return [__DIR__ . '/../stubs/ext-ds.stub'];
    }
}
