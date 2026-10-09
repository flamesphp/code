<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class MagicClassMethodAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function isUnsafeOverridden(ClassMethod $classMethod): bool
    {
        if ($this->nodeNameResolver->isName($classMethod, MethodName::INVOKE)) {
            return \false;
        }
        return $classMethod->isMagic();
    }
}
