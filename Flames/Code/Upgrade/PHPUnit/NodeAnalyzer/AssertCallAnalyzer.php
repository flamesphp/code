<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\TypeWithClassName;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\AstResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Printer\BetterStandardPrinter;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final class AssertCallAnalyzer
{
    private const int MAX_NESTED_METHOD_CALL_LEVEL = 5;
    /**
     * @see https://docs.phpunit.de/en/12.4/assertions.html
     */
    private const string PHPUNIT_FUNCTION_NAMESPACE = 'PHPUnit\Framework\\';
    /**
     * @var string[]
     */
    private const array ASSERT_METHOD_NAME_PREFIXES = ['expectNotToPerformAssertions', 'assert', 'expectException', 'setExpectedException', 'expectOutput', 'should'];
    /**
     * @var array<string, bool>
     */
    private array $containsAssertCallByClassMethod = [];
    /**
     * This should prevent segfaults while going too deep into to parsed code. Without it, it might end-up with segfault
     */
    private int $classMethodNestingLevel = 0;
    public function __construct(private readonly AstResolver $astResolver, private readonly BetterStandardPrinter $betterStandardPrinter, private readonly BetterNodeFinder $betterNodeFinder, private readonly NodeNameResolver $nodeNameResolver, private readonly NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function resetNesting(): void
    {
        $this->classMethodNestingLevel = 0;
    }
    public function containsAssertCall(ClassMethod $classMethod): bool
    {
        ++$this->classMethodNestingLevel;
        try {
            // probably no assert method in the end
            if ($this->classMethodNestingLevel > self::MAX_NESTED_METHOD_CALL_LEVEL) {
                return \false;
            }
            $cacheHash = md5($this->betterStandardPrinter->prettyPrint([$classMethod]));
            if (isset($this->containsAssertCallByClassMethod[$cacheHash])) {
                return $this->containsAssertCallByClassMethod[$cacheHash];
            }
            // A. try "->assert" shallow search first for performance
            $hasDirectAssertOrMockCall = $this->hasDirectAssertOrMockCall($classMethod);
            if ($hasDirectAssertOrMockCall) {
                $this->containsAssertCallByClassMethod[$cacheHash] = $hasDirectAssertOrMockCall;
                return \true;
            }
            // B. look for nested calls
            $hasNestedAssertOrMockCall = $this->hasNestedAssertCall($classMethod);
            $this->containsAssertCallByClassMethod[$cacheHash] = $hasNestedAssertOrMockCall;
            return $hasNestedAssertOrMockCall;
        } finally {
            // restore depth so sibling calls in the same DFS keep their full budget
            --$this->classMethodNestingLevel;
        }
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $call
     */
    public function isAssertMethodCall($call): bool
    {
        if (!$call->name instanceof Identifier) {
            return \false;
        }
        $callName = $this->nodeNameResolver->getName($call->name);
        if (!is_string($callName)) {
            return \false;
        }
        $found = array_any(self::ASSERT_METHOD_NAME_PREFIXES, fn($assertMethodNamePrefix) => str_starts_with($callName, $assertMethodNamePrefix));
        return $found;
    }
    private function hasDirectAssertOrMockCall(ClassMethod $classMethod): bool
    {
        return (bool) $this->betterNodeFinder->findFirst((array) $classMethod->stmts, function (Node $node): bool {
            if ($node instanceof MethodCall) {
                // probably a mock
                if ($this->nodeNameResolver->isName($node->name, 'expects')) {
                    return \true;
                }
                $type = $this->nodeTypeResolver->getType($node->var);
                if ($type instanceof FullyQualifiedObjectType && in_array($type->getClassName(), ['PHPUnit\Framework\MockObject\MockBuilder', 'Prophecy\Prophet'], \true)) {
                    return \true;
                }
                return $this->isAssertMethodCall($node);
            }
            if ($node instanceof StaticCall) {
                return $this->isAssertMethodCall($node);
            }
            // standalone function assert, e.g. "use function PHPUnit\Framework\assertNotNull;"
            if ($node instanceof FuncCall) {
                return $this->isAssertFuncCall($node);
            }
            return \false;
        });
    }
    private function isAssertFuncCall(FuncCall $funcCall): bool
    {
        $funcCallName = $this->nodeNameResolver->getName($funcCall);
        if (!is_string($funcCallName)) {
            return \false;
        }
        if (!str_starts_with($funcCallName, self::PHPUNIT_FUNCTION_NAMESPACE)) {
            return \false;
        }
        $shortFuncCallName = (string) substr($funcCallName, strlen(self::PHPUNIT_FUNCTION_NAMESPACE));
        $found = array_any(self::ASSERT_METHOD_NAME_PREFIXES, fn($assertMethodNamePrefix) => str_starts_with($shortFuncCallName, $assertMethodNamePrefix));
        return $found;
    }
    private function hasNestedAssertCall(ClassMethod $classMethod): bool
    {
        $currentClassMethod = $classMethod;
        // over and over the same method :/
        return (bool) $this->betterNodeFinder->findFirst((array) $classMethod->stmts, function (Node $node) use ($currentClassMethod): bool {
            if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
                return \false;
            }
            // is a mock call
            if ($this->nodeNameResolver->isName($node->name, 'expects')) {
                return \true;
            }
            $classMethod = $this->resolveClassMethodFromCall($node);
            // skip circular self calls
            if ($currentClassMethod === $classMethod) {
                return \false;
            }
            if ($classMethod instanceof ClassMethod) {
                return $this->containsAssertCall($classMethod);
            }
            return \false;
        });
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $call
     */
    private function resolveClassMethodFromCall($call): ?ClassMethod
    {
        if ($call instanceof MethodCall) {
            $objectType = $this->nodeTypeResolver->getType($call->var);
        } else {
            // StaticCall
            $objectType = $this->nodeTypeResolver->getType($call->class);
        }
        if (!$objectType instanceof TypeWithClassName) {
            return null;
        }
        $methodName = $this->nodeNameResolver->getName($call->name);
        if ($methodName === null) {
            return null;
        }
        return $this->astResolver->resolveClassMethod($objectType->getClassName(), $methodName);
    }
}
