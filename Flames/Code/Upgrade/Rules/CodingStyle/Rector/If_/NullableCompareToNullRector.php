<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\If_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as comparing a nullable object to null is ambiguous and weak. Use an "instanceof" check instead, e.g. the instanceof rule set.
 */
final class NullableCompareToNullRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes negate of empty comparison of nullable value to explicit === or !== compare', [new CodeSample(<<<'CODE_SAMPLE'
/** @var stdClass|null $value */
if ($value) {
}

if (!$value) {
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
/** @var stdClass|null $value */
if ($value !== null) {
}

if ($value === null) {
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [If_::class];
    }
    /**
     * @param If_ $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" is deprecated, as null compare is ambiguous and weak; use an "instanceof" check instead', self::class));
    }
}
