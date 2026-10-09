<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class SetUpAssignedMockTypesResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return array<string, string>
     */
    public function resolveFromClass(Class_ $class): array
    {
        $setUpClassMethod = $class->getMethod(MethodName::SET_UP);
        if (!$setUpClassMethod instanceof ClassMethod) {
            return [];
        }
        $propertyNameToMockedTypes = [];
        foreach ((array) $setUpClassMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            $assign = $stmt->expr;
            if (!$assign->expr instanceof MethodCall) {
                continue;
            }
            if (!$this->nodeNameResolver->isNames($assign->expr->name, ['createMock', 'getMockBuilder'])) {
                continue;
            }
            if (!$assign->var instanceof PropertyFetch && !$assign->var instanceof Variable) {
                continue;
            }
            $mockedClassNameExpr = $assign->expr->getArgs()[0]->value;
            if (!$mockedClassNameExpr instanceof ClassConstFetch) {
                continue;
            }
            $propertyOrVariableName = $this->resolvePropertyOrVariableName($assign->var);
            $mockedClass = $this->nodeNameResolver->getName($mockedClassNameExpr->class);
            Assert::string($mockedClass);
            $propertyNameToMockedTypes[$propertyOrVariableName] = $mockedClass;
        }
        return $propertyNameToMockedTypes;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable $propertyFetchOrVariable
     */
    private function resolvePropertyOrVariableName($propertyFetchOrVariable): ?string
    {
        if ($propertyFetchOrVariable instanceof Variable) {
            return $this->nodeNameResolver->getName($propertyFetchOrVariable);
        }
        return $this->nodeNameResolver->getName($propertyFetchOrVariable->name);
    }
}
