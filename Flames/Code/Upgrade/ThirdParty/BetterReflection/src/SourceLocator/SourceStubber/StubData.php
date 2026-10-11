<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\SourceStubber;

/** @internal */
class StubData
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $stub;
    /**
     * @var non-empty-string|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $extensionName;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $fileName;
    /** @param non-empty-string|null $extensionName */
    public function __construct(string $stub, ?string $extensionName, ?string $fileName)
    {
        $this->stub = $stub;
        $this->extensionName = $extensionName;
        $this->fileName = $fileName;
    }
    public function getStub(): string
    {
        return $this->stub;
    }
    /** @return non-empty-string|null */
    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }
    public function getFileName(): ?string
    {
        return $this->fileName;
    }
}
