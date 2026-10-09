<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\Doctrine\TypedCollections\DocBlockProcessor\UnionCollectionTagValueNodeNarrower;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\TypedCollections\Rector\ClassMethod\NarrowParamUnionToCollectionRectorTest
 */
final class NarrowParamUnionToCollectionRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly UnionCollectionTagValueNodeNarrower $unionCollectionTagValueNodeNarrower)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Narrow union param docblock type to Collection type in class method', [new CodeSample(<<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

class SomeClass
{
    /**
     * @param Collection|array $items
     */
    public function run($items)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Doctrine\Common\Collections\Collection;

class SomeClass
{
    /**
     * @param Collection $items
     */
    public function run($items)
    {
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?ClassMethod
    {
        $hasChanged = \false;
        $classMethodPhpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$classMethodPhpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        foreach ($classMethodPhpDocInfo->getParamTagValueNodes() as $paramTagValueNode) {
            $hasNativeCollectionType = $this->isParameterNameNativeCollectionType($node, $paramTagValueNode->parameterName);
            $paramHasChanged = $this->unionCollectionTagValueNodeNarrower->narrow($paramTagValueNode, $hasNativeCollectionType);
            if ($paramHasChanged) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
            return $node;
        }
        return null;
    }
    private function isParameterNameNativeCollectionType(ClassMethod $classMethod, string $parameterName): bool
    {
        foreach ($classMethod->getParams() as $param) {
            if (!$param->var instanceof Variable) {
                continue;
            }
            if (!$this->isName($param->var, ltrim($parameterName, '$'))) {
                continue;
            }
            if (!$param->type instanceof Name) {
                continue;
            }
            return $this->isName($param->type, DoctrineClass::COLLECTION);
        }
        return \false;
    }
}
