<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\CodeQuality\Enum;

use Flames\Code\Upgrade\Doctrine\Enum\MappingClass;
use Flames\Code\Upgrade\Doctrine\Enum\OdmMappingClass;
final class CollectionMapping
{
    /**
     * @var string[]
     */
    public const array TO_MANY_CLASSES = [MappingClass::ONE_TO_MANY, MappingClass::MANY_TO_MANY, OdmMappingClass::REFERENCE_MANY, OdmMappingClass::EMBED_MANY];
    /**
     * @var string[]
     */
    public const array TO_ONE_CLASSES = [MappingClass::MANY_TO_ONE, MappingClass::ONE_TO_ONE, OdmMappingClass::REFERENCE_ONE, OdmMappingClass::EMBED_ONE];
}
