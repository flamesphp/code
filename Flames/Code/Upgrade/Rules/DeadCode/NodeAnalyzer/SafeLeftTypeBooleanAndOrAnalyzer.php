<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
final readonly class SafeLeftTypeBooleanAndOrAnalyzer
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private ExprAnalyzer $exprAnalyzer, private ReflectionResolver $reflectionResolver, private NodeTypeResolver $nodeTypeResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanAnd|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr $booleanAnd
     */
    public function isSafe($booleanAnd): bool
    {
        $hasNonTypedFromParam = (bool) $this->betterNodeFinder->findFirst($booleanAnd->left, fn(Node $node): bool => $node instanceof Variable && $this->exprAnalyzer->isNonTypedFromParam($node));
        if ($hasNonTypedFromParam) {
            return \false;
        }
        $hasPropertyFetchOrArrayDimFetch = (bool) $this->betterNodeFinder->findFirst($booleanAnd->left, static fn(Node $node): bool => $node instanceof PropertyFetch || $node instanceof StaticPropertyFetch || $node instanceof ArrayDimFetch);
        // get type from Property and ArrayDimFetch is unreliable
        if ($hasPropertyFetchOrArrayDimFetch) {
            return \false;
        }
        // skip trait this
        $classReflection = $this->reflectionResolver->resolveClassReflection($booleanAnd);
        if ($classReflection instanceof ClassReflection && $classReflection->isTrait()) {
            return !$booleanAnd->left instanceof Instanceof_;
        }
        return !(bool) $this->betterNodeFinder->findFirst($booleanAnd->left, function (Node $node): bool {
            if (!$node instanceof CallLike) {
                return \false;
            }
            $nativeType = $this->nodeTypeResolver->getNativeType($node);
            return $nativeType instanceof MixedType && !$nativeType->isExplicitMixed();
        });
    }
}
