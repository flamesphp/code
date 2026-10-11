<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\For_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Continue_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\For_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\For_\RemoveDeadContinueRectorTest
 */
final class RemoveDeadContinueRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove useless continue at the end of loops', [new CodeSample(<<<'CODE_SAMPLE'
while ($i < 10) {
    ++$i;
    continue;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
while ($i < 10) {
    ++$i;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Do_::class, For_::class, Foreach_::class, While_::class];
    }
    /**
     * @param Do_|For_|Foreach_|While_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $modified = \false;
        while ($this->canRemoveLastStatement($node->stmts)) {
            array_pop($node->stmts);
            $modified = \true;
        }
        return $modified ? $node : null;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function canRemoveLastStatement(array $stmts): bool
    {
        if ($stmts === []) {
            return \false;
        }
        $lastKey = array_key_last($stmts);
        $lastStmt = $stmts[$lastKey];
        return $this->isRemovable($lastStmt);
    }
    private function isRemovable(Stmt $stmt): bool
    {
        if (!$stmt instanceof Continue_) {
            return \false;
        }
        if ($stmt->num instanceof Int_) {
            return $stmt->num->value < 2;
        }
        return \true;
    }
}
