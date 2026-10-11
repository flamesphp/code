<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class GetterClassMethodPropertyFinder
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param|null
     */
    public function find(ClassMethod $classMethod, Class_ $class)
    {
        // we need exactly one statement of return
        if ($classMethod->stmts === null || count($classMethod->stmts) !== 1) {
            return null;
        }
        $onlyStmt = $classMethod->stmts[0];
        if (!$onlyStmt instanceof Return_) {
            return null;
        }
        if (!$onlyStmt->expr instanceof PropertyFetch) {
            return null;
        }
        $propertyFetch = $onlyStmt->expr;
        if (!$this->nodeNameResolver->isName($propertyFetch->var, 'this')) {
            return null;
        }
        $propertyName = $this->nodeNameResolver->getName($propertyFetch->name);
        if (!is_string($propertyName)) {
            return null;
        }
        $property = $class->getProperty($propertyName);
        if ($property instanceof Property) {
            return $property;
        }
        // try also promoted property in constructor
        $constructClassMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$constructClassMethod instanceof ClassMethod) {
            return null;
        }
        foreach ($constructClassMethod->getParams() as $param) {
            if (!$param->isPromoted()) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($param, $propertyName)) {
                continue;
            }
            return $param;
        }
        return null;
    }
}
