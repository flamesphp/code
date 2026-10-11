<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('returnStatement')]
final class ReturnStatement
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Stmt\Return_ $returnNode;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope,
        Return_ $returnNode
    )
    {
        $this->scope = $scope;
        $this->returnNode = $returnNode;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
    public function getReturnNode(): Return_
    {
        return $this->returnNode;
    }
}
