<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\PrettyPrinter\Standard;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\NarrowIdenticalWithConsecutiveRectorTest
 */
final class NarrowIdenticalWithConsecutiveRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Narrow identical withConsecutive() and willReturnOnConsecutiveCalls() to single call', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function run()
    {
        $this->personServiceMock->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [1],
                [1],
                [1],
            )
            ->willReturnOnConsecutiveCalls(
                [2],
                [2],
                [2],
            );
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function run()
    {
        $this->personServiceMock->expects($this->exactly(3))
            ->method('prepare')
            ->with([1])
            ->willReturn([2]);
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<MethodCall>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }
    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        if (!$this->isNames($node->name, ['withConsecutive', 'willReturnOnConsecutiveCalls'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        // skip as most likely nested array of unique values
        if ($firstArg->unpack) {
            return null;
        }
        // skip new object instances, as each creates a fresh instance with possible property dependency
        if ($this->betterNodeFinder->hasInstancesOf($node->getArgs(), [New_::class])) {
            return null;
        }
        $uniqueArgValues = $this->resolveUniqueArgValues($node);
        // multiple unique values
        if (count($uniqueArgValues) !== 1) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        if ($this->isName($node->name, 'withConsecutive')) {
            $node->name = new Identifier('with');
        } else {
            $node->name = new Identifier('willReturn');
        }
        // use simpler with() instead
        $node->args = [new Arg($firstArg->value)];
        return $node;
    }
    /**
     * @return string[]
     */
    private function resolveUniqueArgValues(MethodCall $methodCall): array
    {
        $printerStandard = new Standard();
        $printedValues = [];
        foreach ($methodCall->getArgs() as $arg) {
            $printedValues[] = $printerStandard->prettyPrintExpr($arg->value);
        }
        return array_unique($printedValues);
    }
}
