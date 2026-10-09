<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ParentClassAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function hasParentCall(ClassMethod $classMethod): bool
    {
        if ($classMethod->isAbstract()) {
            return \false;
        }
        if ($classMethod->isPrivate()) {
            return \false;
        }
        $classMethodName = $classMethod->name->name;
        foreach ((array) $classMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            $expr = $stmt->expr;
            if (!$expr instanceof StaticCall) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($expr->class, 'parent')) {
                continue;
            }
            if ($this->nodeNameResolver->isName($expr->name, $classMethodName)) {
                return \true;
            }
        }
        return \false;
    }
}
