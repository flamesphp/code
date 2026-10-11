<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\Rules\Php80\Contract\ValueObject\AnnotationToAttributeInterface;
use Flames\Code\Upgrade\PhpAttribute\UseAliasNameMatcher;
use Flames\Code\Upgrade\PhpAttribute\ValueObject\UseAliasMetadata;
final readonly class AttributeNameFactory
{
    public function __construct(private UseAliasNameMatcher $useAliasNameMatcher)
    {
    }
    /**
     * @param Use_[] $uses
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name
     */
    public function create(AnnotationToAttributeInterface $annotationToAttribute, DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode, array $uses)
    {
        // A. attribute and class name are the same, so we re-use the short form to keep code compatible with previous one,
        // except start with \
        if ($annotationToAttribute->getAttributeClass() === $annotationToAttribute->getTag()) {
            $attributeName = $doctrineAnnotationTagValueNode->identifierTypeNode->name;
            $attributeName = ltrim($attributeName, '@');
            if (str_starts_with($attributeName, '\\')) {
                return new FullyQualified(ltrim($attributeName, '\\'));
            }
            return new Name($attributeName);
        }
        // B. different name
        $useAliasMetadata = $this->useAliasNameMatcher->match($uses, $doctrineAnnotationTagValueNode->identifierTypeNode->name, $annotationToAttribute);
        if ($useAliasMetadata instanceof UseAliasMetadata) {
            $useUse = $useAliasMetadata->getUseUse();
            // is same as name?
            $useImportName = $useAliasMetadata->getUseImportName();
            if ($useUse->name->toString() !== $useImportName) {
                // no? rename
                $useUse->name = new Name($useImportName);
            }
            return new Name($useAliasMetadata->getShortAttributeName());
        }
        // 3. the class is not aliased and is completely new... return the FQN version
        return new FullyQualified($annotationToAttribute->getAttributeClass());
    }
}
