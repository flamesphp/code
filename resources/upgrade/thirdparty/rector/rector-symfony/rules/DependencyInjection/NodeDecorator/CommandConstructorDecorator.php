<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\DependencyInjection\NodeDecorator;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class CommandConstructorDecorator
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function decorate(Class_ $class): void
    {
        // special case for command to keep parent constructor call
        if (!$this->nodeTypeResolver->isObjectType($class, new ObjectType('Symfony\Component\Console\Command\Command'))) {
            return;
        }
        $constructClassMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$constructClassMethod instanceof ClassMethod) {
            return;
        }
        // empty stmts? add parent::__construct() to setup command
        if ((array) $constructClassMethod->stmts === []) {
            $parentConstructStaticCall = new StaticCall(new Name('parent'), '__construct');
            $constructClassMethod->stmts[] = new Expression($parentConstructStaticCall);
        }
    }
}
