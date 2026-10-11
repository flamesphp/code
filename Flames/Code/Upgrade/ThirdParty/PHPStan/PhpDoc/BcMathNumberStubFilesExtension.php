<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
#[AutowiredService]
final class BcMathNumberStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getFiles(): array
    {
        if (!($this->phpVersion->versionId >= 80400)) {
            return [];
        }
        return [__DIR__ . '/../stubs/bcmath.stub'];
    }
}
