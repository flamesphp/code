<?php

declare (strict_types=1);
namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Reflection\ReflectionProvider;
use function strtolower;
/**
 * @implements Collector<Expression, array{string, int}>
 */
#[RegisteredCollector(4)]
final class PossiblyPureNewCollector implements Collector
{
    private ReflectionProvider $reflectionProvider;
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return Expression::class;
    }
    public function processNode(Node $node, Scope $scope)
    {
        if (!$node->expr instanceof Node\Expr\New_) {
            return null;
        }
        if (!$node->expr->class instanceof Node\Name) {
            return null;
        }
        $className = $node->expr->class->name;
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }
        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasConstructor()) {
            return null;
        }
        $constructor = $classReflection->getConstructor();
        if (strtolower($constructor->getName()) !== '__construct') {
            return null;
        }
        if (!$constructor->isPure()->maybe()) {
            return null;
        }
        return [$constructor->getDeclaringClass()->getName(), $node->getStartLine()];
    }
}
