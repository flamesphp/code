<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ClassAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function hasImplements(Class_ $class, string $interfaceFQN): bool
    {
        $found = array_any($class->implements, fn($name) => $this->nodeNameResolver->isName($name, $interfaceFQN));
        return $found;
    }
}
