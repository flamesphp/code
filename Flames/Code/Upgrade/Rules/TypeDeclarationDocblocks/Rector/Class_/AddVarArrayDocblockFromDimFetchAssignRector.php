<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeDocblockTypeDecorator;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\ArrayDimFetchFinder;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TagNodeAnalyzer\UsefulArrayTagNodeAnalyzer;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\Class_\AddVarArrayDocblockFromDimFetchAssignRectorTest
 */
final class AddVarArrayDocblockFromDimFetchAssignRector extends AbstractRector
{
    public function __construct(private readonly TypeFactory $typeFactory, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly UsefulArrayTagNodeAnalyzer $usefulArrayTagNodeAnalyzer, private readonly NodeDocblockTypeDecorator $nodeDocblockTypeDecorator, private readonly ArrayDimFetchFinder $arrayDimFetchFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add @var array property docblock from dim fetches on property fetch assignment', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    private array $items = [];

    public function run()
    {
        $this->items[] = [
            'name' => 'John',
        ];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @var array<array<string, string>>
     */
    private array $items = [];

    public function run()
    {
        $this->items[] = [
            'name' => 'John',
        ];
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getProperties() as $property) {
            if (!$this->isPropertyTypeArray($property)) {
                continue;
            }
            $propertyPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
            if ($this->usefulArrayTagNodeAnalyzer->isUsefulArrayTag($propertyPhpDocInfo->getVarTagValueNode())) {
                continue;
            }
            $propertyName = $this->getName($property);
            // a keyed dim assign ($this->prop['x'] = ...) or a direct assign carries a value type this rule does not read from bare appends; skip to avoid a too-narrow @var
            if ($this->arrayDimFetchFinder->hasNonAppendAssignToPropertyName($node, $propertyName)) {
                continue;
            }
            $assignedExprs = $this->arrayDimFetchFinder->findDimFetchAssignToPropertyName($node, $propertyName);
            $assignedExprTypes = [];
            foreach ($assignedExprs as $assignedExpr) {
                $assignedExprTypes[] = $this->getType($assignedExpr);
            }
            // nothing to add
            if ($assignedExprTypes === []) {
                continue;
            }
            $uniqueGeneralizedUnionTypes = $this->typeFactory->uniquateTypes($assignedExprTypes);
            if (count($uniqueGeneralizedUnionTypes) > 1) {
                $generalizedUnionedTypes = new UnionType($uniqueGeneralizedUnionTypes);
            } else {
                $generalizedUnionedTypes = $uniqueGeneralizedUnionTypes[0];
            }
            $arrayReturnType = new ArrayType(new MixedType(), $generalizedUnionedTypes);
            $hasPropertyChanged = $this->nodeDocblockTypeDecorator->decorateGenericIterableVarType($arrayReturnType, $propertyPhpDocInfo, $property);
            if ($hasPropertyChanged === \false) {
                continue;
            }
            $hasChanged = \true;
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function isPropertyTypeArray(Property $property): bool
    {
        if (!$property->type instanceof Identifier) {
            return \false;
        }
        return $this->isName($property->type, 'array');
    }
}
