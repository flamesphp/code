<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\NodeFinder;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
final readonly class LocalMethodCallFinder
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeTypeResolver $nodeTypeResolver, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return MethodCall[]|StaticCall[]
     */
    public function match(Class_ $class, ClassMethod $classMethod): array
    {
        $className = $this->nodeNameResolver->getName($class);
        if (!is_string($className)) {
            return [];
        }
        $classMethodName = $this->nodeNameResolver->getName($classMethod);
        /** @var MethodCall[]|StaticCall[] $matchingMethodCalls */
        $matchingMethodCalls = $this->betterNodeFinder->find($class->getMethods(), function (Node $subNode) use ($className, $classMethodName): bool {
            if (!$subNode instanceof MethodCall && !$subNode instanceof StaticCall) {
                return \false;
            }
            if (!$this->nodeNameResolver->isName($subNode->name, $classMethodName)) {
                return \false;
            }
            $callerType = $subNode instanceof MethodCall ? $this->nodeTypeResolver->getType($subNode->var) : $this->nodeTypeResolver->getType($subNode->class);
            return ClassNameFromObjectTypeResolver::resolve($callerType) === $className;
        });
        return $matchingMethodCalls;
    }
}
