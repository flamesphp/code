<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeDocblockTypeDecorator;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\GetterClassMethodPropertyFinder;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TagNodeAnalyzer\UsefulArrayTagNodeAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\DocblockGetterReturnArrayFromPropertyDocblockVarRectorTest
 */
final class DocblockGetterReturnArrayFromPropertyDocblockVarRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly UsefulArrayTagNodeAnalyzer $usefulArrayTagNodeAnalyzer, private readonly NodeDocblockTypeDecorator $nodeDocblockTypeDecorator, private readonly GetterClassMethodPropertyFinder $getterClassMethodPropertyFinder)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add @return array docblock to a getter method based on @var of the property', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @var int[]
     */
    private array $items;

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
        if ($node->isAnonymous()) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->returnType instanceof Node) {
                continue;
            }
            if (!$this->isName($classMethod->returnType, 'array')) {
                continue;
            }
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);
            if ($this->usefulArrayTagNodeAnalyzer->isUsefulArrayTag($phpDocInfo->getReturnTagValue())) {
                continue;
            }
            $propertyOrParam = $this->getterClassMethodPropertyFinder->find($classMethod, $node);
            if (!$propertyOrParam instanceof Node) {
                continue;
            }
            $propertyDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($propertyOrParam);
            $varTagValueNode = $propertyDocInfo->getVarTagValueNode();
            if (!$varTagValueNode instanceof VarTagValueNode) {
                continue;
            }
            // is type useful?
            if (!$varTagValueNode->type instanceof GenericTypeNode && !$varTagValueNode->type instanceof ArrayTypeNode) {
                continue;
            }
            if (!$this->nodeDocblockTypeDecorator->decorateGenericIterableReturnType($varTagValueNode->type, $phpDocInfo, $classMethod)) {
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
