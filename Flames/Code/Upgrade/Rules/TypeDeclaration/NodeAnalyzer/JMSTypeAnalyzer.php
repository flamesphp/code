<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\Doctrine\CodeQuality\Enum\CollectionMapping;
use Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer\AttributeFinder;
use Flames\Code\Upgrade\Enum\ClassName;
use Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Util\StringUtils;
final readonly class JMSTypeAnalyzer
{
    public function __construct(private AttributeFinder $attributeFinder, private PhpAttributeAnalyzer $phpAttributeAnalyzer, private ValueResolver $valueResolver)
    {
    }
    public function hasAtLeastOneUntypedPropertyUsingJmsAttribute(Class_ $class): bool
    {
        foreach ($class->getProperties() as $property) {
            if ($property->type instanceof Node) {
                continue;
            }
            if ($this->attributeFinder->hasAttributeByClasses($property, [ClassName::JMS_TYPE])) {
                return \true;
            }
        }
        return \false;
    }
    public function hasPropertyJMSTypeAttribute(Property $property): bool
    {
        if (!$this->phpAttributeAnalyzer->hasPhpAttribute($property, ClassName::JMS_TYPE)) {
            return \false;
        }
        // most likely collection, not sole type
        return !$this->phpAttributeAnalyzer->hasPhpAttributes($property, array_merge(CollectionMapping::TO_MANY_CLASSES, CollectionMapping::TO_ONE_CLASSES));
    }
    public function resolveTypeAttributeValue(Property $property): ?string
    {
        $jmsTypeAttribute = $this->attributeFinder->findAttributeByClass($property, ClassName::JMS_TYPE);
        if (!$jmsTypeAttribute instanceof Attribute) {
            return null;
        }
        $typeValue = $this->valueResolver->getValue($jmsTypeAttribute->args[0]->value);
        if (!is_string($typeValue)) {
            return null;
        }
        if (StringUtils::isMatch($typeValue, '#DateTime\<(.*?)\>#')) {
            // special case for DateTime, which is not a scalar type
            return 'DateTime';
        }
        return $typeValue;
    }
}
