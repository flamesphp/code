<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\StringCastAssertStringContainsStringRectorTest
 */
final class StringCastAssertStringContainsStringRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Cast 2nd argument in assertStringContainsString() to a string if not yet', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeTest extends TestCase
{
    public function testSomething(?string $value)
    {
        $this->assertStringContainsString('foo', $value);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class SomeTest extends TestCase
{
    public function testSomething(?string $value)
    {
        $this->assertStringContainsString('foo', (string) $value);
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<MethodCall|StaticCall>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }
    /**
     * @param MethodCall|StaticCall $node
     * @return \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|null
     */
    public function refactor(Node $node)
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['assertStringContainsString', 'assertStringNotContainsString'])) {
            return null;
        }
        $secondArg = $node->getArgs()[1];
        $secondArgType = $this->getType($secondArg->value);
        if ($secondArgType->isString()->yes()) {
            return null;
        }
        $secondArg->value = new String_($secondArg->value);
        return $node;
    }
}
