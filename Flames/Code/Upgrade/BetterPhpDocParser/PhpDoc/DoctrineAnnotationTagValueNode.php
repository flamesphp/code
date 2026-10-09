<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\AbstractValuesAwareNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Stringable;
final class DoctrineAnnotationTagValueNode extends AbstractValuesAwareNode
{
    /**
     * @param ArrayItemNode[] $values
     */
    public function __construct(public IdentifierTypeNode $identifierTypeNode, ?string $originalContent = null, array $values = [], ?string $silentKey = null)
    {
        $this->hasChanged = \true;
        parent::__construct($values, $originalContent, $silentKey);
    }
    public function __toString(): string
    {
        if (!$this->hasChanged) {
            if ($this->originalContent === null) {
                return '';
            }
            return $this->originalContent;
        }
        if ($this->values === []) {
            if ($this->originalContent === '()') {
                // empty brackets
                return $this->originalContent;
            }
            return '';
        }
        $itemContents = $this->printValuesContent($this->values);
        return \sprintf('(%s)', $itemContents);
    }
    public function hasClassName(string $className): bool
    {
        $annotationName = trim($this->identifierTypeNode->name, '@');
        if ($annotationName === $className) {
            return \true;
        }
        // the name is not fully qualified in the original name, look for resolved class attribute
        $resolvedClass = $this->identifierTypeNode->getAttribute(PhpDocAttributeKey::RESOLVED_CLASS);
        return $resolvedClass === $className;
    }
}
