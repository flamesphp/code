<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
final class MethodCallNameAnalyzer
{
    public function isLocalMethodCallNamed(Expr $expr, string $desiredMethodName): bool
    {
        if (!$expr instanceof MethodCall) {
            return \false;
        }
        if (!$expr->var instanceof Variable) {
            return \false;
        }
        if ($expr->var->name !== 'this') {
            return \false;
        }
        if (!$expr->name instanceof Identifier) {
            return \false;
        }
        return $expr->name->toString() === $desiredMethodName;
    }
    public function isParentMethodCall(Class_ $class, Expr $expr): bool
    {
        if (!$class->extends instanceof Name) {
            return \false;
        }
        $parentClassName = $class->extends->toString();
        if ($class->getMethod($parentClassName) instanceof ClassMethod) {
            return \false;
        }
        return $this->isLocalMethodCallNamed($expr, $parentClassName);
    }
}
