<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\Break_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitor;
use Flames\Code\Upgrade\NodeNestingScope\ContextAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\Break_\BreakNotInLoopOrSwitchToReturnRectorTest
 */
final class BreakNotInLoopOrSwitchToReturnRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ContextAnalyzer $contextAnalyzer)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_BREAK_OUTSIDE_LOOP;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Convert break outside for/foreach/switch context to return', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        if ($isphp5)
            return 1;
        else
            return 2;
        break;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        if ($isphp5)
            return 1;
        else
            return 2;
        return;
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
        return [Break_::class];
    }
    /**
     * @param Break_ $node
     * @return Return_|null|NodeVisitor::REMOVE_NODE
     */
    public function refactor(Node $node)
    {
        if ($this->contextAnalyzer->isInLoop($node)) {
            return null;
        }
        if ($this->contextAnalyzer->isInIf($node)) {
            return new Return_();
        }
        return NodeVisitor::REMOVE_NODE;
    }
}
