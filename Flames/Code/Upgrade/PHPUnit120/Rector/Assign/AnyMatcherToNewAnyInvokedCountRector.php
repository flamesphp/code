<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit120\Rector\Assign;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * The TestCase::any() method was deprecated in PHPUnit 12.5
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/6461
 *
 * @see \Flames\Code\Upgrade\PHPUnit120\Rector\Assign\AnyMatcherToNewAnyInvokedCountRectorTest
 */
final class AnyMatcherToNewAnyInvokedCountRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    private const string ANY_INVOKED_COUNT_CLASS = 'PHPUnit\Framework\MockObject\Rule\AnyInvokedCount';
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=12.5');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change deprecated `$this->any()` matcher assign to direct `new AnyInvokedCount()`', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): void
    {
        $matcher = $this->any();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test(): void
    {
        $matcher = new AnyInvokedCount();
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
        return [Assign::class];
    }
    /**
     * @param Assign $node
     */
    public function refactor(Node $node): ?Assign
    {
        if (!$node->expr instanceof MethodCall) {
            return null;
        }
        $methodCall = $node->expr;
        if ($methodCall->isFirstClassCallable()) {
            return null;
        }
        if (!$this->isName($methodCall->name, 'any')) {
            return null;
        }
        if ($methodCall->getArgs() !== []) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isPHPUnitTestCaseCall($methodCall)) {
            return null;
        }
        $node->expr = new New_(new FullyQualified(self::ANY_INVOKED_COUNT_CLASS));
        return $node;
    }
}
