<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\ArgumentMover;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\IdentifierManipulator;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\ValueObject\ConstantWithAssertMethods;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertSameBoolNullToSpecificMethodRectorTest
 */
final class AssertSameBoolNullToSpecificMethodRector extends AbstractRector
{
    /**
     * @var ConstantWithAssertMethods[]
     */
    private readonly array $constantWithAssertMethods;
    public function __construct(private readonly IdentifierManipulator $identifierManipulator, private readonly ArgumentMover $argumentMover, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
        $this->constantWithAssertMethods = [new ConstantWithAssertMethods('null', 'assertNull', 'assertNotNull'), new ConstantWithAssertMethods('true', 'assertTrue', 'assertNotTrue'), new ConstantWithAssertMethods('false', 'assertFalse', 'assertNotFalse')];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns same bool and null comparisons to their method name alternatives in PHPUnit TestCase', [new CodeSample('$this->assertSame(null, $anything);', '$this->assertNull($anything);'), new CodeSample('$this->assertNotSame(false, $anything);', '$this->assertNotFalse($anything);')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }
    /**
     * @param MethodCall|StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['assertSame', 'assertNotSame'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArgumentValue = $node->getArgs()[0]->value;
        if (!$firstArgumentValue instanceof ConstFetch) {
            return null;
        }
        foreach ($this->constantWithAssertMethods as $constantWithAssertMethod) {
            if (!$this->isName($firstArgumentValue, $constantWithAssertMethod->getConstant())) {
                continue;
            }
            $this->renameMethod($node, $constantWithAssertMethod);
            $this->argumentMover->removeFirstArg($node);
            return $node;
        }
        return null;
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall $node
     */
    private function renameMethod($node, ConstantWithAssertMethods $constantWithAssertMethods): void
    {
        $this->identifierManipulator->renameNodeWithMap($node, ['assertSame' => $constantWithAssertMethods->getAssetMethodName(), 'assertNotSame' => $constantWithAssertMethods->getNotAssertMethodName()]);
    }
}
