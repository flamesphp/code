<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
final class FetchedNodesResult
{
    /**
     * @var array<string, list<FetchedNode<Node\Stmt\ClassLike>>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $classNodes;
    /**
     * @var array<string, list<FetchedNode<Node\Stmt\Function_>>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $functionNodes;
    /**
     * @var array<string, list<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $constantNodes;
    /**
     * @param array<string, list<FetchedNode<Node\Stmt\ClassLike>>> $classNodes
     * @param array<string, list<FetchedNode<Node\Stmt\Function_>>> $functionNodes
     * @param array<string, list<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>> $constantNodes
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $classNodes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $functionNodes,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $constantNodes
    )
    {
        $this->classNodes = $classNodes;
        $this->functionNodes = $functionNodes;
        $this->constantNodes = $constantNodes;
    }
    /**
     * @return array<string, list<FetchedNode<Node\Stmt\ClassLike>>>
     */
    public function getClassNodes(): array
    {
        return $this->classNodes;
    }
    /**
     * @return array<string, list<FetchedNode<Node\Stmt\Function_>>>
     */
    public function getFunctionNodes(): array
    {
        return $this->functionNodes;
    }
    /**
     * @return array<string, list<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
     */
    public function getConstantNodes(): array
    {
        return $this->constantNodes;
    }
}
