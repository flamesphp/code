<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ParentCallDetector
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function hasParentCall(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toString();
        foreach ((array) $classMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof StaticCall) {
                continue;
            }
            $staticCall = $stmt->expr;
            if (!$this->nodeNameResolver->isName($staticCall->class, 'parent')) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($staticCall->name, $methodName)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
}
