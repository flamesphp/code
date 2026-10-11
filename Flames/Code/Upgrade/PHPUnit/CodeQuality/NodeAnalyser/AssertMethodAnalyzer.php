<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\Enum\NonAssertNonStaticMethods;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
final readonly class AssertMethodAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private ReflectionResolver $reflectionResolver, private NodeTypeResolver $nodeTypeResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $call
     */
    public function detectTestCaseCall($call): bool
    {
        $objectCaller = $call instanceof MethodCall ? $call->var : $call->class;
        if (!$this->nodeTypeResolver->isObjectType($objectCaller, new ObjectType(PHPUnitClassName::TEST_CASE))) {
            return \false;
        }
        $methodName = $this->nodeNameResolver->getName($call->name);
        if (!str_starts_with((string) $methodName, 'assert') && !in_array($methodName, NonAssertNonStaticMethods::ALL, \true)) {
            return \false;
        }
        if ($call instanceof StaticCall && !$this->nodeNameResolver->isNames($call->class, ['static', 'self'])) {
            return \false;
        }
        $extendedMethodReflection = $this->resolveMethodReflection($call);
        if (!$extendedMethodReflection instanceof ExtendedMethodReflection) {
            return \false;
        }
        // only handle methods in TestCase or Assert class classes
        $declaringClassName = $extendedMethodReflection->getDeclaringClass()->getName();
        return in_array($declaringClassName, [PHPUnitClassName::TEST_CASE, PHPUnitClassName::ASSERT], \true);
    }
    public function detectTestCaseCallForStatic(MethodCall $methodCall): bool
    {
        if (!$this->detectTestCaseCall($methodCall)) {
            return \false;
        }
        $extendedMethodReflection = $this->resolveMethodReflection($methodCall);
        return $extendedMethodReflection instanceof ExtendedMethodReflection && $extendedMethodReflection->isStatic();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $call
     */
    private function resolveMethodReflection($call): ?ExtendedMethodReflection
    {
        $methodName = $this->nodeNameResolver->getName($call->name);
        if ($methodName === null) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($call);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        return $classReflection->getNativeMethod($methodName);
    }
}
