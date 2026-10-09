<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\ValueObjectFactory;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\PropertyRename;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use FlamesPrefix202610\Webmozart\Assert\InvalidArgumentException;
final readonly class PropertyRenameFactory
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function createFromExpectedName(Class_ $class, Property $property, string $expectedName): ?PropertyRename
    {
        $currentName = $this->nodeNameResolver->getName($property);
        $className = (string) $this->nodeNameResolver->getName($class);
        try {
            return new PropertyRename($property, $expectedName, $currentName, $class, $className, $property->props[0]);
        } catch (InvalidArgumentException) {
        }
        return null;
    }
}
