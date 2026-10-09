<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\FamilyTree\Reflection\FamilyRelationsAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeRecursiveDirectoryIteratorHasChildrenRectorTest
 */
final class DowngradeRecursiveDirectoryIteratorHasChildrenRector extends AbstractRector
{
    public function __construct(private readonly FamilyRelationsAnalyzer $familyRelationsAnalyzer)
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
        return new RuleDefinition('Remove bool type hint on child of RecursiveDirectoryIterator hasChildren allowLinks parameter', [new CodeSample(<<<'CODE_SAMPLE'
class RecursiveDirectoryIteratorChild extends \RecursiveDirectoryIterator
{
    public function hasChildren(bool $allowLinks = false): bool
    {
        return true;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class RecursiveDirectoryIteratorChild extends \RecursiveDirectoryIterator
{
    public function hasChildren($allowLinks = false): bool
    {
        return true;
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
        foreach ($node->getMethods() as $classMethod) {
            if (!isset($classMethod->params[0])) {
                continue;
            }
            if ($classMethod->params[0]->type === null) {
                continue;
            }
            if (!$this->isName($classMethod, 'hasChildren')) {
                continue;
            }
            $ancestorClassNames = $this->familyRelationsAnalyzer->getClassLikeAncestorNames($node);
            if (!in_array('RecursiveDirectoryIterator', $ancestorClassNames, \true)) {
                continue;
            }
            $classMethod->params[0]->type = null;
            return $node;
        }
        return null;
    }
}
