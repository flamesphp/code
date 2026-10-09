<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitAttribute;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
final readonly class TestsNodeAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private NodeNameResolver $nodeNameResolver, private PhpDocInfoFactory $phpDocInfoFactory, private ReflectionResolver $reflectionResolver)
    {
    }
    public function isInTestClass(Node $node): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        // traits have no parent, so the test case check below can never match them
        if ($classReflection->isTrait()) {
            return $this->isInTestTrait($classReflection, $node);
        }
        $found = array_any(PHPUnitClassName::TEST_CLASSES, fn($testCaseObjectClass) => $classReflection->is($testCaseObjectClass));
        return $found;
    }
    public function isTestClassMethod(ClassMethod $classMethod): bool
    {
        if (!$classMethod->isPublic()) {
            return \false;
        }
        if (str_starts_with($classMethod->name->toString(), 'test')) {
            return \true;
        }
        foreach ($classMethod->getAttrGroups() as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attribute) {
                if ($this->nodeNameResolver->isName($attribute->name, PHPUnitAttribute::TEST)) {
                    return \true;
                }
            }
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
        return $phpDocInfo->hasByName('test');
    }
    public function isAssertMethodCallName(Node $node, string $name): bool
    {
        if ($node instanceof StaticCall) {
            $callerType = $this->nodeTypeResolver->getType($node->class);
        } elseif ($node instanceof MethodCall) {
            $callerType = $this->nodeTypeResolver->getType($node->var);
        } else {
            return \false;
        }
        $assertObjectType = new ObjectType(PHPUnitClassName::ASSERT);
        if (!$assertObjectType->isSuperTypeOf($callerType)->yes()) {
            return \false;
        }
        /** @var StaticCall|MethodCall $node */
        return $this->nodeNameResolver->isName($node->name, $name);
    }
    /**
     * Test traits live next to the test cases that use them, so the namespace is the main hint.
     * Only public non-static methods can be test methods, and a "test" prefixed one is a test
     * method even outside a tests namespace.
     */
    private function isInTestTrait(ClassReflection $classReflection, Node $node): bool
    {
        if (!$node instanceof ClassMethod) {
            return $this->isInTestsNamespace($classReflection);
        }
        if (!$node->isPublic()) {
            return \false;
        }
        if ($node->isStatic()) {
            return \false;
        }
        if ($this->isInTestsNamespace($classReflection)) {
            return \true;
        }
        return str_starts_with($node->name->toString(), 'test');
    }
    private function isInTestsNamespace(ClassReflection $classReflection): bool
    {
        $nameParts = explode('\\', $classReflection->getName());
        // drop the short trait name, only the namespace matters here
        array_pop($nameParts);
        $found = array_any($nameParts, fn($namePart) => in_array($namePart, ['Test', 'Tests'], \true));
        return $found;
    }
    /**
     * @param string[] $names
     */
    public function isPHPUnitMethodCallNames(Node $node, array $names): bool
    {
        if (!$this->isPHPUnitTestCaseCall($node)) {
            return \false;
        }
        /** @var MethodCall|StaticCall $node */
        return $this->nodeNameResolver->isNames($node->name, $names);
    }
    public function isPHPUnitTestCaseCall(Node $node): bool
    {
        if ($node instanceof MethodCall) {
            $callerType = $this->nodeTypeResolver->getType($node->var);
            if ($callerType instanceof StaticType) {
                $callerType = $callerType->getStaticObjectType();
            }
            if ($callerType instanceof ObjectType) {
                if ($callerType->isInstanceOf(PHPUnitClassName::TEST_CASE)->yes()) {
                    return \true;
                }
                if ($callerType->isInstanceOf(PHPUnitClassName::ASSERT)->yes()) {
                    return \true;
                }
            }
            return \false;
        }
        if ($node instanceof StaticCall) {
            $classType = $this->nodeTypeResolver->getType($node->class);
            if ($classType instanceof ObjectType) {
                if ($classType->isInstanceOf(PHPUnitClassName::TEST_CASE)->yes()) {
                    return \true;
                }
                if ($classType->isInstanceOf(PHPUnitClassName::ASSERT)->yes()) {
                    return \true;
                }
            }
        }
        return \false;
    }
}
