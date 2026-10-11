<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ClassManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param string[] $interfaceFQNS
     */
    public function removeImplements(Class_ $class, array $interfaceFQNS): void
    {
        foreach ($class->implements as $key => $implement) {
            if (!$this->nodeNameResolver->isNames($implement, $interfaceFQNS)) {
                continue;
            }
            unset($class->implements[$key]);
        }
    }
}
