<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\Rules\Php84\NodeFactory\ForeachToArrayFindFactory;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\ValueObject\PolyfillPackage;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\RelatedPolyfillInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayFindRectorTest
 */
final class ForeachToArrayFindRector extends AbstractRector implements MinPhpVersionInterface, RelatedPolyfillInterface
{
    public function __construct(private readonly ForeachToArrayFindFactory $foreachToArrayFindFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace foreach with assignment and break with array_find', [new CodeSample(<<<'CODE_SAMPLE'
$found = null;
foreach ($animals as $animal) {
    if (str_starts_with($animal, 'c')) {
        $found = $animal;
        break;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$found = array_find($animals, fn($animal) => str_starts_with($animal, 'c'));
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return NodeGroup::STMTS_AWARE;
    }
    /**
     * @param StmtsAware $node
     */
    public function refactor(Node $node): ?Node
    {
        return $this->foreachToArrayFindFactory->createArrayFindAssign($node, 'array_find', \false);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARRAY_FIND;
    }
    public function providePolyfillPackage(): string
    {
        return PolyfillPackage::PHP_84;
    }
}
