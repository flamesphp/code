<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\PropertyRenamer;

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VarLikeIdentifier;
use Flames\Code\Upgrade\Rules\Naming\Guard\PropertyConflictingNameGuard\MatchPropertyTypeConflictingNameGuard;
use Flames\Code\Upgrade\Rules\Naming\RenameGuard\PropertyRenameGuard;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\PropertyRename;
final readonly class MatchTypePropertyRenamer
{
    public function __construct(private MatchPropertyTypeConflictingNameGuard $matchPropertyTypeConflictingNameGuard, private PropertyRenameGuard $propertyRenameGuard, private \Flames\Code\Upgrade\Rules\Naming\PropertyRenamer\PropertyFetchRenamer $propertyFetchRenamer)
    {
    }
    public function rename(PropertyRename $propertyRename): ?Property
    {
        if ($this->matchPropertyTypeConflictingNameGuard->isConflicting($propertyRename)) {
            return null;
        }
        if ($propertyRename->isAlreadyExpectedName()) {
            return null;
        }
        if ($this->propertyRenameGuard->shouldSkip($propertyRename)) {
            return null;
        }
        $propertyItem = $propertyRename->getPropertyProperty();
        $propertyItem->name = new VarLikeIdentifier($propertyRename->getExpectedName());
        $this->renamePropertyFetchesInClass($propertyRename);
        return $propertyRename->getProperty();
    }
    private function renamePropertyFetchesInClass(PropertyRename $propertyRename): void
    {
        $this->propertyFetchRenamer->renamePropertyFetchesInClass($propertyRename->getClassLike(), $propertyRename->getCurrentName(), $propertyRename->getExpectedName());
    }
}
