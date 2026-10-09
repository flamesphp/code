<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AttributeArrayNameInliner;
use Flames\Code\Upgrade\PhpAttribute\Contract\AnnotationToAttributeMapperInterface;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
/**
 * @implements AnnotationToAttributeMapperInterface<DoctrineAnnotationTagValueNode>
 */
final class DoctrineAnnotationAnnotationToAttributeMapper implements AnnotationToAttributeMapperInterface
{
    private AnnotationToAttributeMapper $annotationToAttributeMapper;
    public function __construct(private readonly PhpVersionProvider $phpVersionProvider, private readonly AttributeArrayNameInliner $attributeArrayNameInliner)
    {
    }
    /**
     * Avoid circular reference
     */
    public function autowire(AnnotationToAttributeMapper $annotationToAttributeMapper): void
    {
        $this->annotationToAttributeMapper = $annotationToAttributeMapper;
    }
    /**
     * @param mixed $value
     */
    public function isCandidate($value): bool
    {
        if (!$value instanceof DoctrineAnnotationTagValueNode) {
            return \false;
        }
        return $this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::NEW_INITIALIZERS);
    }
    /**
     * @param DoctrineAnnotationTagValueNode $value
     */
    public function map($value): New_
    {
        $annotationShortName = $this->resolveAnnotationName($value);
        $values = $value->getValues();
        if ($values !== []) {
            $argValues = $this->annotationToAttributeMapper->map($value->getValues());
            if ($argValues instanceof Array_) {
                // create named args
                $args = $this->attributeArrayNameInliner->inlineArrayToArgs($argValues);
            } else {
                throw new ShouldNotHappenException();
            }
        } else {
            $args = [];
        }
        return new New_(new Name($annotationShortName), $args);
    }
    private function resolveAnnotationName(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): string
    {
        $annotationShortName = $doctrineAnnotationTagValueNode->identifierTypeNode->name;
        return ltrim($annotationShortName, '@');
    }
}
