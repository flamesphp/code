<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

/** @api */
final class TypeAliasImportTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $importedAlias;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $importedFrom;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $importedAs;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $importedAlias,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $importedFrom,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $importedAs
    )
    {
        $this->importedAlias = $importedAlias;
        $this->importedFrom = $importedFrom;
        $this->importedAs = $importedAs;
    }
    public function getImportedAlias(): string
    {
        return $this->importedAlias;
    }
    public function getImportedFrom(): string
    {
        return $this->importedFrom;
    }
    public function getImportedAs(): ?string
    {
        return $this->importedAs;
    }
}
