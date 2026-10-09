<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp73\Rector\Unset_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Unset_;
use Flames\Code\Upgrade\DowngradePhp73\Tokenizer\FollowedByCommaAnalyzer;
use Flames\Code\Upgrade\DowngradePhp73\Tokenizer\TrailingCommaRemover;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp73\Rector\Unset_\DowngradeTrailingCommasInUnsetRectorTest
 */
final class DowngradeTrailingCommasInUnsetRector extends AbstractRector
{
    public function __construct(private readonly FollowedByCommaAnalyzer $followedByCommaAnalyzer, private readonly TrailingCommaRemover $trailingCommaRemover)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove trailing commas in unset', [new CodeSample(<<<'CODE_SAMPLE'
unset(
	$x,
	$y,
);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
unset(
	$x,
	$y
);
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Unset_::class];
    }
    /**
     * @param Unset_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->vars !== []) {
            $lastArgumentPosition = array_key_last($node->vars);
            $last = $node->vars[$lastArgumentPosition];
            if (!$this->followedByCommaAnalyzer->isFollowed($this->file, $last)) {
                return null;
            }
            $this->trailingCommaRemover->remove($this->file, $last);
            return $node;
        }
        return null;
    }
}
