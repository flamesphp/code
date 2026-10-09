<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\AttributeGroup;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver;
use Flames\Code\Upgrade\Rules\Php80\NodeFactory\AttrGroupsFactory;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\AnnotationToAttribute;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\DoctrineTagAndAnnotationToAttribute;
/**
 * @api used in Upgrade packages
 */
final readonly class GenericAnnotationToAttributeConverter
{
    public function __construct(private AttrGroupsFactory $attrGroupsFactory, private ReflectionProvider $reflectionProvider, private UseImportsResolver $useImportsResolver, private PhpDocInfoFactory $phpDocInfoFactory, private PhpDocTagRemover $phpDocTagRemover)
    {
    }
    public function convert(Node $node, AnnotationToAttribute $annotationToAttribute): ?AttributeGroup
    {
        if (!$this->isExistingAttributeClass($annotationToAttribute)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $uses = $this->useImportsResolver->resolveBareUses();
        return $this->processDoctrineAnnotationClass($phpDocInfo, $uses, $annotationToAttribute);
    }
    /**
     * @param Use_[] $uses
     */
    private function processDoctrineAnnotationClass(PhpDocInfo $phpDocInfo, array $uses, AnnotationToAttribute $annotationToAttribute): ?AttributeGroup
    {
        if ($phpDocInfo->getPhpDocNode()->children === []) {
            return null;
        }
        $doctrineTagAndAnnotationToAttributes = [];
        $doctrineTagValueNodes = [];
        foreach ($phpDocInfo->getPhpDocNode()->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            if (!$phpDocChildNode->value instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            $doctrineTagValueNode = $phpDocChildNode->value;
            if (!$doctrineTagValueNode->hasClassName($annotationToAttribute->getTag())) {
                continue;
            }
            $doctrineTagAndAnnotationToAttributes[] = new DoctrineTagAndAnnotationToAttribute($doctrineTagValueNode, $annotationToAttribute);
            $doctrineTagValueNodes[] = $doctrineTagValueNode;
        }
        $attributeGroups = $this->attrGroupsFactory->create($doctrineTagAndAnnotationToAttributes, $uses);
        foreach ($doctrineTagValueNodes as $doctrineTagValueNode) {
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineTagValueNode);
        }
        return $attributeGroups[0] ?? null;
    }
    private function isExistingAttributeClass(AnnotationToAttribute $annotationToAttribute): bool
    {
        // make sure the attribute class really exists to avoid error on early upgrade
        if (!$this->reflectionProvider->hasClass($annotationToAttribute->getAttributeClass())) {
            return \false;
        }
        // make sure the class is marked as attribute
        $classReflection = $this->reflectionProvider->getClass($annotationToAttribute->getAttributeClass());
        return $classReflection->isAttributeClass();
    }
}
