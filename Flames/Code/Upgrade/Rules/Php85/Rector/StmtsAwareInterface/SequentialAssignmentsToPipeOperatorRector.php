<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php85\Rector\StmtsAwareInterface;

use PhpParser\Node;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/pipe-operator-v3
 *
 * @deprecated This rule is deprecated, as merging sequential assignments into a single |> pipe removes intermediate variables that carry naming and can be re-used later. It also depends on the context of surrounding code and can create extremely long chains that break readability.
 */
final class SequentialAssignmentsToPipeOperatorRector extends AbstractRector implements MinPhpVersionInterface, DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Transform sequential assignments to pipe operator syntax', [new CodeSample(<<<'CODE_SAMPLE'
$value = "hello world";
$result1 = function1($value);
$result2 = function2($result1);

$result = function3($result2);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$value = "hello world";

$result = $value
    |> function1(...)
    |> function2(...)
    |> function3(...);
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return NodeGroup::STMTS_AWARE;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::PIPE_OPERATOER;
    }
    /**
     * @param StmtsAware $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" is deprecated, as it removes intermediate variables that carry naming and can be re-used later', self::class));
    }
}
