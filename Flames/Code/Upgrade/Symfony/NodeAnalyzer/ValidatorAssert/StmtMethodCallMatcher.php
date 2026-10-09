<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer\ValidatorAssert;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class StmtMethodCallMatcher
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function match(Stmt $stmt, string $methodName): ?MethodCall
    {
        if (!$stmt instanceof Expression) {
            return null;
        }
        if (!$stmt->expr instanceof MethodCall) {
            return null;
        }
        $methodCall = $stmt->expr;
        if (!$this->nodeNameResolver->isName($methodCall->name, $methodName)) {
            return null;
        }
        return $methodCall;
    }
}
