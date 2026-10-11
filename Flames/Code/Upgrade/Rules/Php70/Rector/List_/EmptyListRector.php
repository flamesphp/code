<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\List_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\List_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\List_\EmptyListRectorTest
 */
final class EmptyListRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('list() cannot be empty', [new CodeSample(<<<'CODE_SAMPLE'
'list() = $values;'
CODE_SAMPLE
, <<<'CODE_SAMPLE'
'list($unusedGenerated) = $values;'
CODE_SAMPLE
)]);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NO_EMPTY_LIST;
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [List_::class];
    }
    /**
     * @param List_ $node
     */
    public function refactor(Node $node): ?List_
    {
        foreach ($node->items as $item) {
            if ($item instanceof ArrayItem) {
                return null;
            }
        }
        $node->items[0] = new ArrayItem(new Variable('unusedGenerated'));
        return $node;
    }
}
