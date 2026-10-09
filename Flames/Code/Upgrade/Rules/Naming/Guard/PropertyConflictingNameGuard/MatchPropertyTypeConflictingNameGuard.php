<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Guard\PropertyConflictingNameGuard;

use PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\Rules\Naming\ExpectedNameResolver\MatchPropertyTypeExpectedNameResolver;
use Flames\Code\Upgrade\Rules\Naming\PhpArray\ArrayFilter;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\PropertyRename;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class MatchPropertyTypeConflictingNameGuard
{
    public function __construct(private MatchPropertyTypeExpectedNameResolver $matchPropertyTypeExpectedNameResolver, private NodeNameResolver $nodeNameResolver, private ArrayFilter $arrayFilter)
    {
    }
    public function isConflicting(PropertyRename $propertyRename): bool
    {
        $conflictingPropertyNames = $this->resolve($propertyRename->getClassLike());
        return in_array($propertyRename->getExpectedName(), $conflictingPropertyNames, \true);
    }
    /**
     * @return string[]
     */
    private function resolve(ClassLike $classLike): array
    {
        $expectedNames = [];
        foreach ($classLike->getProperties() as $property) {
            $expectedName = $this->matchPropertyTypeExpectedNameResolver->resolve($property, $classLike);
            $expectedName ??= $this->nodeNameResolver->getName($property);
            $expectedNames[] = $expectedName;
        }
        return $this->arrayFilter->filterWithAtLeastTwoOccurrences($expectedNames);
    }
}
