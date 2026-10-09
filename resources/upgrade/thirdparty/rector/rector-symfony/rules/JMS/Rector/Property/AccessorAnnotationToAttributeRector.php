<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\JMS\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
use Flames\Code\Upgrade\PhpAttribute\GenericAnnotationToAttributeConverter;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\Enum\JMSAnnotation;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\JMS\Rector\Property\AccessorAnnotationToAttributeRectorTest
 */
final class AccessorAnnotationToAttributeRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly DocBlockUpdater $docBlockUpdater, private readonly ValueResolver $valueResolver, private readonly GenericAnnotationToAttributeConverter $genericAnnotationToAttributeConverter)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('jms/serializer', '>=3.14');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes @Accessor annotation to #[Accessor] attribute with specific "getter" or "setter" keys', [new CodeSample(<<<'CODE_SAMPLE'
use JMS\Serializer\Annotation\Accessor;

class User
{
    /**
     * @Accessor("getValue")
     */
    private $value;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use JMS\Serializer\Annotation\Accessor;

class User
{
    #[Accessor(getter: 'getValue')]
    private $value;
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Property::class];
    }
    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?\PhpParser\Node\Stmt\Property
    {
        $annotationToAttribute = new AnnotationToAttribute(JMSAnnotation::ACCESSOR);
        $attributeGroup = $this->genericAnnotationToAttributeConverter->convert($node, $annotationToAttribute);
        if (!$attributeGroup instanceof AttributeGroup) {
            return null;
        }
        $attribute = $attributeGroup->attrs[0];
        foreach ($attribute->args as $attributeArg) {
            // already known
            if ($attributeArg->name instanceof Identifier) {
                continue;
            }
            $value = $this->valueResolver->getValue($attributeArg->value);
            if (str_starts_with((string) $value, 'get')) {
                $attributeArg->name = new Identifier('getter');
            } elseif (str_starts_with((string) $value, 'set')) {
                $attributeArg->name = new Identifier('setter');
            } else {
                // skip, not getter/setter
                continue;
            }
        }
        // 2. Reprint docblock
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        $node->attrGroups = array_merge($node->attrGroups, [$attributeGroup]);
        return $node;
    }
}
