<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_;

use PhpParser\Node;
use Flames\Code\Upgrade\Rules\Php84\NodeFactory\ForeachToArrayAnyAllFactory;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\ValueObject\PolyfillPackage;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\VersionBonding\Contract\RelatedPolyfillInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php84\Rector\Foreach_\ForeachToArrayAllRectorTest
 */
final class ForeachToArrayAllRector extends AbstractRector implements MinPhpVersionInterface, RelatedPolyfillInterface
{
    public function __construct(private readonly ForeachToArrayAnyAllFactory $foreachToArrayAnyAllFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace foreach with boolean assignment and break OR foreach with early return with array_all', [new CodeSample(<<<'CODE_SAMPLE'
$found = true;
foreach ($animals as $animal) {
    if (!str_starts_with($animal, 'c')) {
        $found = false;
        break;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$found = array_all($animals, fn($animal) => str_starts_with($animal, 'c'));
CODE_SAMPLE
), new CodeSample(<<<'CODE_SAMPLE'
foreach ($animals as $animal) {
    if (!str_starts_with($animal, 'c')) {
        return false;
    }
}
return true;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
return array_all($animals, fn($animal) => str_starts_with($animal, 'c'));
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
        return $this->foreachToArrayAnyAllFactory->refactorToArrayAnyAll($node, 'array_all', \true, \true, \false);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARRAY_ALL;
    }
    public function providePolyfillPackage(): string
    {
        return PolyfillPackage::PHP_84;
    }
}
