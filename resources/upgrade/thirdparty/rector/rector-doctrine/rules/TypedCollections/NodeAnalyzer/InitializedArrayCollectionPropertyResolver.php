<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\TypedCollections\NodeAnalyzer;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class InitializedArrayCollectionPropertyResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return string[]
     */
    public function resolve(Class_ $class): array
    {
        $constructorClassMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$constructorClassMethod instanceof ClassMethod) {
            return [];
        }
        $initializedPropertyNames = [];
        foreach ((array) $constructorClassMethod->stmts as $constructorStmt) {
            if (!$constructorStmt instanceof Expression) {
                continue;
            }
            if (!$constructorStmt->expr instanceof Assign) {
                continue;
            }
            $assign = $constructorStmt->expr;
            if (!$this->isNewArrayCollection($assign->expr)) {
                continue;
            }
            if (!$assign->var instanceof PropertyFetch) {
                continue;
            }
            $propertyFetch = $assign->var;
            $initializedPropertyNames[] = (string) $this->nodeNameResolver->getName($propertyFetch->name);
        }
        return $initializedPropertyNames;
    }
    private function isNewArrayCollection(Expr $expr): bool
    {
        if (!$expr instanceof New_) {
            return \false;
        }
        return $this->nodeNameResolver->isName($expr->class, DoctrineClass::ARRAY_COLLECTION);
    }
}
