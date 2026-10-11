<?php

declare (strict_types=1);
namespace PHPStan\Node\Constant;

use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('classConstantFetch')]
final class ClassConstantFetch
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassConstFetch $node;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassConstFetch $node,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope
    )
    {
        $this->node = $node;
        $this->scope = $scope;
    }
    public function getNode(): ClassConstFetch
    {
        return $this->node;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
}
