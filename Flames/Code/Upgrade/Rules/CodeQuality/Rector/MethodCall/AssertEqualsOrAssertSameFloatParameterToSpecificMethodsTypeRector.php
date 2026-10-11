<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeFactory\AssertCallFactory;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see  \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRectorTest
 */
final class AssertEqualsOrAssertSameFloatParameterToSpecificMethodsTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly AssertCallFactory $assertCallFactory, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change assertEquals()/assertSame() method using float on expected argument to new specific alternatives.', [new CodeSample(
            // code before
            <<<'CODE_SAMPLE'
$this->assertSame(10.20, $value);
$this->assertEquals(10.200, $value);
CODE_SAMPLE
,
            <<<'CODE_SAMPLE'
$this->assertEqualsWithDelta(10.20, $value, PHP_FLOAT_EPSILON);
$this->assertEqualsWithDelta(10.200, $value, PHP_FLOAT_EPSILON);
CODE_SAMPLE

        )]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['assertEquals', 'assertSame'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $args = $node->getArgs();
        $firstValue = $args[0]->value;
        if (!$firstValue instanceof Float_) {
            return null;
        }
        $customMessageArg = $args[2] ?? null;
        $newMethodCall = $this->assertCallFactory->createCallWithName($node, 'assertEqualsWithDelta');
        $newMethodCall->args[0] = $args[0];
        $newMethodCall->args[1] = $args[1];
        $newMethodCall->args[2] = new Arg(new ConstFetch(new Name('PHP_FLOAT_EPSILON')));
        if ($customMessageArg instanceof Arg) {
            $newMethodCall->args[] = $customMessageArg;
        }
        return $newMethodCall;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_72;
    }
}
