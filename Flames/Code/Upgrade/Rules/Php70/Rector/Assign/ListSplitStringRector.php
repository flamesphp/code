<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\Assign;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\List_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\Assign\ListSplitStringRectorTest
 */
final class ListSplitStringRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('list() cannot split string directly anymore, use str_split()', [new CodeSample('list($foo) = "string";', 'list($foo) = str_split("string");')]);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_LIST_SPLIT_STRING;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Assign::class];
    }
    /**
     * @param Assign $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->var instanceof List_) {
            return null;
        }
        $exprType = $this->getType($node->expr);
        if (!$exprType->isString()->yes()) {
            return null;
        }
        $node->expr = $this->nodeFactory->createFuncCall('str_split', [$node->expr]);
        return $node;
    }
}
