<?php

declare (strict_types=1);
namespace PHPStan\Node\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('gatheredMethodCall')]
final class MethodCall
{
    /**
     * @var \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\Array_
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $node;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\Array_ $node
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $node,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope
    )
    {
        $this->node = $node;
        $this->scope = $scope;
    }
    /**
     * @return Node\Expr\MethodCall|StaticCall|Array_
     */
    public function getNode()
    {
        return $this->node;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
}
