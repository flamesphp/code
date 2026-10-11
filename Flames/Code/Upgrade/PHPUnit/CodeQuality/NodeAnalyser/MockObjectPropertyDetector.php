<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
final readonly class MockObjectPropertyDetector
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function detect(Property $property, string $className = PHPUnitClassName::MOCK_OBJECT): bool
    {
        if (!$property->type instanceof FullyQualified) {
            return \false;
        }
        return $property->type->toString() === $className;
    }
    /**
     * @return array<string, MethodCall|StaticCall>
     */
    public function collectFromClassMethod(ClassMethod $classMethod, string $methodName = 'createMock'): array
    {
        $propertyNamesToCreateMockMethodCalls = [];
        foreach ((array) $classMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            $assign = $stmt->expr;
            if (!$assign->var instanceof PropertyFetch) {
                continue;
            }
            // both $this->createMock() and self::createMock()
            if (!$assign->expr instanceof MethodCall && !$assign->expr instanceof StaticCall) {
                continue;
            }
            $createCall = $assign->expr;
            if (!$this->nodeNameResolver->isName($createCall->name, $methodName)) {
                continue;
            }
            $propertyFetch = $assign->var;
            $propertyName = $this->nodeNameResolver->getName($propertyFetch->name);
            if (!is_string($propertyName)) {
                continue;
            }
            $propertyNamesToCreateMockMethodCalls[$propertyName] = $createCall;
        }
        return $propertyNamesToCreateMockMethodCalls;
    }
}
