<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\PhpDocNodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node as PhpNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateObjectType;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeTypeResolver\ValueObject\OldToNewType;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor;
use Flames\Code\Upgrade\Rules\Renaming\Collector\RenamedNameCollector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
final class ClassRenamePhpDocNodeVisitor extends AbstractPhpDocNodeVisitor
{
    /**
     * @var OldToNewType[]
     */
    private array $oldToNewTypes = [];
    private bool $hasChanged = \false;
    private ?PhpNode $currentPhpNode = null;
    public function __construct(private readonly StaticTypeMapper $staticTypeMapper, private readonly RenamedNameCollector $renamedNameCollector)
    {
    }
    public function setCurrentPhpNode(PhpNode $phpNode): void
    {
        $this->currentPhpNode = $phpNode;
    }
    public function beforeTraverse(Node $node): void
    {
        if ($this->oldToNewTypes === []) {
            throw new ShouldNotHappenException('Configure "$oldToNewClasses" first');
        }
        if (!$this->currentPhpNode instanceof PhpNode) {
            throw new ShouldNotHappenException('Configure "$currentPhpNode" first');
        }
        $this->hasChanged = \false;
    }
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof IdentifierTypeNode) {
            return null;
        }
        /** @var \Flames\Code\Upgrade\ThirdParty\PhpParser\Node $currentPhpNode */
        $currentPhpNode = $this->currentPhpNode;
        $staticType = $this->staticTypeMapper->mapPHPStanPhpDocTypeNodeToPHPStanType($node, $currentPhpNode);
        // non object type and @template is to not be renamed
        if (!$staticType instanceof ObjectType || $staticType instanceof TemplateObjectType) {
            return null;
        }
        // make sure to compare FQNs
        $objectType = $this->ensureFQCNObject($staticType, $node->name);
        foreach ($this->oldToNewTypes as $oldToNewType) {
            $oldType = $oldToNewType->getOldType();
            if (!$oldType instanceof ObjectType) {
                continue;
            }
            if (!$objectType->equals($oldType)) {
                continue;
            }
            $newTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPHPStanPhpDocTypeNode($oldToNewType->getNewType());
            $parentType = $node->getAttribute(PhpDocAttributeKey::PARENT);
            if ($parentType instanceof TypeNode) {
                // mirror attributes
                $newTypeNode->setAttribute(PhpDocAttributeKey::PARENT, $parentType);
            }
            $this->hasChanged = \true;
            $this->renamedNameCollector->add($oldType->getClassName());
            return $newTypeNode;
        }
        return null;
    }
    /**
     * @param OldToNewType[] $oldToNewTypes
     */
    public function setOldToNewTypes(array $oldToNewTypes): void
    {
        $this->oldToNewTypes = $oldToNewTypes;
    }
    public function hasChanged(): bool
    {
        return $this->hasChanged;
    }
    private function ensureFQCNObject(ObjectType $objectType, string $identifierName): ObjectType
    {
        if ($objectType instanceof ShortenedObjectType && str_starts_with($identifierName, '\\')) {
            return new ObjectType(ltrim($identifierName, '\\'));
        }
        if ($objectType instanceof ShortenedObjectType || $objectType instanceof AliasedObjectType) {
            return new ObjectType($objectType->getFullyQualifiedName());
        }
        return $objectType;
    }
}
