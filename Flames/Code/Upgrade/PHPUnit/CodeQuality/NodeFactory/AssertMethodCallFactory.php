<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableNameToType;
final class AssertMethodCallFactory
{
    public function createAssertInstanceOf(VariableNameToType $variableNameToType): Expression
    {
        $args = [new Arg(new ClassConstFetch(new FullyQualified($variableNameToType->getObjectType()), 'class')), new Arg(new Variable($variableNameToType->getVariableName()))];
        $methodCall = new MethodCall(new Variable('this'), 'assertInstanceOf', $args);
        return new Expression($methodCall);
    }
}
