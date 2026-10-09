<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeDocblockTypeDecorator;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\PropertyGetterFinder;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TagNodeAnalyzer\UsefulArrayTagNodeAnalyzer;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\DocblockVarArrayFromGetterReturnRectorTest
 */
final class DocblockVarArrayFromGetterReturnRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly PropertyGetterFinder $propertyGetterFinder, private readonly UsefulArrayTagNodeAnalyzer $usefulArrayTagNodeAnalyzer, private readonly NodeDocblockTypeDecorator $nodeDocblockTypeDecorator)
    {
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add @var array property docblock from its getter @return', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private array $items;

    /**
     * @return int[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var int[]
     */
    private array $items;

    /**
     * @return int[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            // property type must be known
            if (!$property->type instanceof Identifier) {
                continue;
            }
            if (!$this->isName($property->type, 'array')) {
                continue;
            }
            if (count($property->props) > 1) {
                continue;
            }
            $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
            // type is already known, skip it
            if ($this->usefulArrayTagNodeAnalyzer->isUsefulArrayTag($propertyPhpDocInfo->getVarTagValueNode())) {
                continue;
            }
            $propertyGetterMethod = $this->propertyGetterFinder->find($property, $node);
            if (!$propertyGetterMethod instanceof ClassMethod) {
                continue;
            }
            $classMethodDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($propertyGetterMethod);
            $returnTagValueNode = $classMethodDocInfo->getReturnTagValue();
            if (!$returnTagValueNode instanceof ReturnTagValueNode) {
                continue;
            }
            $isPropertyChanged = $this->nodeDocblockTypeDecorator->decorateGenericIterableVarType($classMethodDocInfo->getReturnType(), $propertyPhpDocInfo, $property);
            if (!$isPropertyChanged) {
                continue;
            }
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
