<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Strict\Rector\Empty_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BooleanNot;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Empty_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as it creates unreadable code with messy isset()/comparison checks. Refactor the value to a single sole type instead, then the empty() check can be replaced by a clear comparison.
 */
final class DisallowedEmptyRuleFixerRector extends AbstractRector implements ConfigurableRectorInterface, DeprecatedInterface
{
    /**
     * @api
     */
    public const string TREAT_AS_NON_EMPTY = 'treat_as_non_empty';
    /**
     * @param array<string, mixed> $configuration
     */
    public function configure(array $configuration): void
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change empty() check to explicit strict comparisons', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
final class SomeEmptyArray
{
    public function run(array $items)
    {
        return empty($items);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeEmptyArray
{
    public function run(array $items)
    {
        return $items === [];
    }
}
CODE_SAMPLE
, [self::TREAT_AS_NON_EMPTY => \false])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Empty_::class, BooleanNot::class];
    }
    /**
     * @param Empty_|BooleanNot $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as it creates unreadable code with messy checks; refactor the value to a sole type instead', self::class));
    }
}
