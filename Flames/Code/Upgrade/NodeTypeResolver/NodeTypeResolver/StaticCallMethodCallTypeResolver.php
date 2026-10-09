<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverAwareInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Contract\NodeTypeResolverInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
/**
 * @implements NodeTypeResolverInterface<StaticCall|MethodCall>
 */
final class StaticCallMethodCallTypeResolver implements NodeTypeResolverInterface, NodeTypeResolverAwareInterface
{
    private NodeTypeResolver $nodeTypeResolver;
    public function __construct(private readonly NodeNameResolver $nodeNameResolver)
    {
    }
    public function autowire(NodeTypeResolver $nodeTypeResolver): void
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeClasses(): array
    {
        return [StaticCall::class, MethodCall::class];
    }
    /**
     * @param StaticCall|MethodCall $node
     */
    public function resolve(Node $node): Type
    {
        $methodName = $this->nodeNameResolver->getName($node->name);
        // no specific method found, return class types, e.g. <ClassType>::$method()
        if (!is_string($methodName)) {
            return new MixedType();
        }
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return new MixedType();
        }
        $nodeReturnType = $scope->getType($node);
        if (!$nodeReturnType instanceof MixedType) {
            return $nodeReturnType;
        }
        if ($node instanceof MethodCall) {
            $callerType = $this->nodeTypeResolver->getType($node->var);
        } else {
            $callerType = $this->nodeTypeResolver->getType($node->class);
        }
        foreach ($callerType->getObjectClassReflections() as $objectClassReflection) {
            $classMethodReturnType = $this->resolveClassMethodReturnType($objectClassReflection, $node, $methodName, $scope);
            if (!$classMethodReturnType instanceof MixedType) {
                return $classMethodReturnType;
            }
        }
        return new MixedType();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $node
     */
    private function resolveClassMethodReturnType(ClassReflection $classReflection, $node, string $methodName, Scope $scope): Type
    {
        foreach ($classReflection->getAncestors() as $ancestorClassReflection) {
            if (!$ancestorClassReflection->hasMethod($methodName)) {
                continue;
            }
            $methodReflection = $ancestorClassReflection->getMethod($methodName, $scope);
            if ($methodReflection instanceof PhpMethodReflection) {
                $parametersAcceptorWithPhpDocs = ParametersAcceptorSelectorVariantsWrapper::select($methodReflection, $node, $scope);
                return $parametersAcceptorWithPhpDocs->getReturnType();
            }
        }
        return new MixedType();
    }
}
