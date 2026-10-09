<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\JMS\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
use Flames\Code\Upgrade\PhpAttribute\GenericAnnotationToAttributeConverter;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Symfony\Enum\JMSAnnotation;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://github.com/schmittjoh/serializer/issues/1531
 *
 * @see \Flames\Code\Upgrade\JMS\Rector\Class_\AccessTypeAnnotationToAttributeRectorTest
 */
final class AccessTypeAnnotationToAttributeRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly DocBlockUpdater $docBlockUpdater, private readonly GenericAnnotationToAttributeConverter $genericAnnotationToAttributeConverter)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('jms/serializer', '>=3.14');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes @AccessType annotation to #[AccessType] attribute with specific key', [new CodeSample(<<<'CODE_SAMPLE'
use JMS\Serializer\Annotation\AccessType;

/** @AccessType("public_method") */
class User
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use JMS\Serializer\Annotation\AccessType;

#[AccessType(values: ['public_method'])]
class User
{
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class];
    }
    /**
     * @param Class_|Property $node
     * @return \PhpParser\Node\Stmt\Class_|\PhpParser\Node\Stmt\Property|null
     */
    public function refactor(Node $node)
    {
        $annotationToAttribute = new AnnotationToAttribute(JMSAnnotation::ACCESS_TYPE);
        $attributeGroup = $this->genericAnnotationToAttributeConverter->convert($node, $annotationToAttribute);
        if (!$attributeGroup instanceof AttributeGroup) {
            return null;
        }
        $attribute = $attributeGroup->attrs[0];
        if (count($attribute->args) === 1) {
            $soleArg = $attribute->args[0];
            $soleArg->name = new Identifier('type');
        }
        // 2. Reprint docblock
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        $node->attrGroups = array_merge($node->attrGroups, [$attributeGroup]);
        return $node;
    }
}
