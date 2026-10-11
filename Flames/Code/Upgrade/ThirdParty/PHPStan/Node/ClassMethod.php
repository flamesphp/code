<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('gatheredClassMethod')]
final class ClassMethod
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PhpParser\Node\Stmt\ClassMethod $node;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isDeclaredInTrait;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PhpParser\Node\Stmt\ClassMethod $node,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isDeclaredInTrait
    )
    {
        $this->node = $node;
        $this->isDeclaredInTrait = $isDeclaredInTrait;
    }
    public function getNode(): \PhpParser\Node\Stmt\ClassMethod
    {
        return $this->node;
    }
    public function isDeclaredInTrait(): bool
    {
        return $this->isDeclaredInTrait;
    }
}
