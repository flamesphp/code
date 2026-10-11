<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ValueObject;

use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final readonly class UsedImports
{
    /**
     * @param array<FullyQualifiedObjectType|AliasedObjectType> $useImports
     * @param FullyQualifiedObjectType[] $functionImports
     * @param FullyQualifiedObjectType[] $constantImports
     */
    public function __construct(private array $useImports, private array $functionImports, private array $constantImports)
    {
    }
    /**
     * @return array<FullyQualifiedObjectType|AliasedObjectType>
     */
    public function getUseImports(): array
    {
        return $this->useImports;
    }
    /**
     * @return FullyQualifiedObjectType[]
     */
    public function getFunctionImports(): array
    {
        return $this->functionImports;
    }
    /**
     * @return FullyQualifiedObjectType[]
     */
    public function getConstantImports(): array
    {
        return $this->constantImports;
    }
}
