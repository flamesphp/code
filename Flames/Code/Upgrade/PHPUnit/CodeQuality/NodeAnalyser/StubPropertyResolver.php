<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
final readonly class StubPropertyResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private ValueResolver $valueResolver)
    {
    }
    /**
     * @return array<string, string>
     */
    public function resolveFromClassMethod(ClassMethod $classMethod): array
    {
        $propertyNamesToStubClasses = [];
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
            if (!$assign->expr instanceof MethodCall) {
                continue;
            }
            $methodCall = $assign->expr;
            if (!$this->nodeNameResolver->isName($methodCall->name, 'createStub')) {
                continue;
            }
            $propertyFetch = $assign->var;
            $propertyName = $this->nodeNameResolver->getName($propertyFetch->name);
            if (!is_string($propertyName)) {
                continue;
            }
            $firstArg = $methodCall->getArgs()[0];
            $stubbedClassName = $this->valueResolver->getValue($firstArg->value);
            $propertyNamesToStubClasses[$propertyName] = $stubbedClassName;
        }
        return $propertyNamesToStubClasses;
    }
}
