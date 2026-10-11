<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php72\Rector\Unset_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\Unset_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php72\Rector\Unset_\UnsetCastRectorTest
 */
final class UnsetCastRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_UNSET_CAST;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove `(unset)` cast', [new CodeSample(<<<'CODE_SAMPLE'
$different = (unset) $value;

$value = (unset) $value;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$different = null;

unset($value);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Unset_::class, Assign::class, Expression::class];
    }
    /**
     * @param Unset_|Assign|Expression $node
     * @return NodeVisitor::REMOVE_NODE|Node|null
     */
    public function refactor(Node $node)
    {
        if ($node instanceof Assign) {
            return $this->refactorAssign($node);
        }
        if ($node instanceof Expression) {
            if (!$node->expr instanceof Unset_) {
                return null;
            }
            return NodeVisitor::REMOVE_NODE;
        }
        return $this->nodeFactory->createNull();
    }
    private function refactorAssign(Assign $assign): ?FuncCall
    {
        if (!$assign->expr instanceof Unset_) {
            return null;
        }
        $unset = $assign->expr;
        if (!$this->nodeComparator->areNodesEqual($assign->var, $unset->expr)) {
            return null;
        }
        return $this->nodeFactory->createFuncCall('unset', [$assign->var]);
    }
}
