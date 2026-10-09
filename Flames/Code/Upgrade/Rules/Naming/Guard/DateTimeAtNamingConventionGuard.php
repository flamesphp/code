<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Guard;

use DateTimeInterface;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\PropertyRename;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Utils\TypeUnwrapper;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
use Flames\Code\Upgrade\Util\StringUtils;
final readonly class DateTimeAtNamingConventionGuard
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private TypeUnwrapper $typeUnwrapper)
    {
    }
    public function isConflicting(PropertyRename $propertyRename): bool
    {
        $type = $this->nodeTypeResolver->getType($propertyRename->getProperty());
        $type = $this->typeUnwrapper->unwrapFirstObjectTypeFromUnionType($type);
        $className = ClassNameFromObjectTypeResolver::resolve($type);
        if ($className === null) {
            return \false;
        }
        if (!is_a($className, DateTimeInterface::class, \true)) {
            return \false;
        }
        return StringUtils::isMatch($propertyRename->getCurrentName(), \Flames\Code\Upgrade\Rules\Naming\Guard\BreakingVariableRenameGuard::AT_NAMING_REGEX);
    }
}
