<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\StaticCall;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @deprecated as risky change with little value. Use manually or custom rule where needed instead.
 */
final class StaticCallOnNonStaticToInstanceCallRector extends AbstractRector implements MinPhpVersionInterface, DeprecatedInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::INSTANCE_CALL;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes static call to instance call, where not useful', [new CodeSample(<<<'CODE_SAMPLE'
class Something
{
    public function doWork()
    {
    }
}

class Another
{
    public function run()
    {
        return Something::doWork();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class Something
{
    public function doWork()
    {
    }
}

class Another
{
    public function run()
    {
        return (new Something)->doWork();
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
        return [StaticCall::class];
    }
    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" is deprecated as risky change with little value. Use manually or custom rule where needed instead', self::class));
    }
}
