<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\ArrayType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeAnalyzer\ConstructorAssignedTypeResolver;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeDocblockTypeDecorator;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TagNodeAnalyzer\UsefulArrayTagNodeAnalyzer;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\DocblockVarFromParamDocblockInConstructorRectorTest
 */
final class DocblockVarFromParamDocblockInConstructorRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly ConstructorAssignedTypeResolver $constructorAssignedTypeResolver, private readonly UsefulArrayTagNodeAnalyzer $usefulArrayTagNodeAnalyzer, private readonly NodeDocblockTypeDecorator $nodeDocblockTypeDecorator)
    {
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add @var array docblock to a property based on @param of constructor assign', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private array $items;

    /**
     * @param string[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var string[]
     */
    private array $items;

    /**
     * @param string[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
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
        $constructorClassMethod = $node->getMethod(MethodName::CONSTRUCT);
        if (!$constructorClassMethod instanceof ClassMethod) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if (!$this->isArrayTypedProperty($property)) {
                continue;
            }
            $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
            // @var tag already given
            if ($this->usefulArrayTagNodeAnalyzer->isUsefulArrayTag($propertyPhpDocInfo->getVarTagValueNode())) {
                continue;
            }
            $propertyName = $this->getName($property);
            $assignedType = $this->constructorAssignedTypeResolver->resolve($node, $propertyName);
            if (!$assignedType instanceof ArrayType) {
                continue;
            }
            $hasPropertyChanged = $this->nodeDocblockTypeDecorator->decorateGenericIterableVarType($assignedType, $propertyPhpDocInfo, $property);
            if (!$hasPropertyChanged) {
                continue;
            }
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function isArrayTypedProperty(Property $property): bool
    {
        if (!$property->type instanceof Node) {
            return \false;
        }
        return $this->isName($property->type, 'array');
    }
}
