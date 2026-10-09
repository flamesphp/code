<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\Encapsed;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\InterpolatedString;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as it is a coding standard preference with no real value. Use a coding standard tool instead.
 */
final class WrapEncapsedVariableInCurlyBracesRector extends AbstractRector implements DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Wrap encapsed variables in curly braces', [new CodeSample(<<<'CODE_SAMPLE'
function run($world)
{
    echo "Hello $world!";
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function run($world)
{
    echo "Hello {$world}!";
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [InterpolatedString::class];
    }
    /**
     * @param InterpolatedString $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as it is a coding standard preference with no real value', self::class));
    }
}
