<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\TypeAlias;
/**
 * @api
 */
final class TypeAliasTag
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $aliasName;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public TypeNode $typeNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public NameScope $nameScope;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $aliasName,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        TypeNode $typeNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        NameScope $nameScope
    )
    {
        $this->aliasName = $aliasName;
        $this->typeNode = $typeNode;
        $this->nameScope = $nameScope;
    }
    public function getAliasName(): string
    {
        return $this->aliasName;
    }
    public function getTypeAlias(): TypeAlias
    {
        return new TypeAlias($this->typeNode, $this->nameScope);
    }
}
