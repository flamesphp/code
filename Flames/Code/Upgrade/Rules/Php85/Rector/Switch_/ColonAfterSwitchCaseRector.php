<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php85\Rector\Switch_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php85\Rector\Switch_\ColonAfterSwitchCaseRectorTest
 */
final class ColonAfterSwitchCaseRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change deprecated semicolon to colon after switch case', [new CodeSample(<<<'CODE_SAMPLE'
switch ($value) {
    case 'baz';
        echo 'baz';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
switch ($value) {
    case 'baz':
        echo 'baz';
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Switch_::class];
    }
    /**
     * @param Switch_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        $oldTokens = $this->getFile()->getOldTokens();
        foreach ($node->cases as $key => $case) {
            $cond = $case->cond;
            if (!$cond instanceof Expr) {
                continue;
            }
            $endTokenPos = $cond->getEndTokenPos();
            if ($endTokenPos < 0) {
                continue;
            }
            if (count($case->stmts) === 0) {
                $startCaseStmtsPos = isset($node->cases[$key + 1]) ? $node->cases[$key + 1]->getStartTokenPos() : $node->getEndTokenPos();
            } else {
                $startCaseStmtsPos = $case->stmts[0]->getStartTokenPos();
            }
            if ($startCaseStmtsPos < 0) {
                continue;
            }
            $nextTokenPos = $endTokenPos;
            while (++$nextTokenPos < $startCaseStmtsPos) {
                if (!isset($oldTokens[$nextTokenPos])) {
                    continue 2;
                }
                $nextToken = $oldTokens[$nextTokenPos];
                if (trim($nextToken->text) === '') {
                    continue;
                }
                if ($nextToken->text === ':') {
                    continue 2;
                }
                if ($nextToken->text === ';') {
                    $hasChanged = \true;
                    $nextToken->text = ':';
                    continue 2;
                }
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::COLON_AFTER_SWITCH_CASE;
    }
}
