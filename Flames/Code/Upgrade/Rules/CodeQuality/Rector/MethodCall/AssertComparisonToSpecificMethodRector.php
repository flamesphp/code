<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Greater;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Smaller;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ConstantScalarType;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\IdentifierManipulator;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\PHPUnit\ValueObject\BinaryOpWithAssertMethod;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertComparisonToSpecificMethodRectorTest
 */
final class AssertComparisonToSpecificMethodRector extends AbstractRector
{
    /**
     * @var BinaryOpWithAssertMethod[]
     */
    private readonly array $binaryOpWithAssertMethods;
    public function __construct(private readonly IdentifierManipulator $identifierManipulator, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
        $this->binaryOpWithAssertMethods = [new BinaryOpWithAssertMethod(Identical::class, 'assertSame', 'assertNotSame'), new BinaryOpWithAssertMethod(NotIdentical::class, 'assertNotSame', 'assertSame'), new BinaryOpWithAssertMethod(Equal::class, 'assertEquals', 'assertNotEquals'), new BinaryOpWithAssertMethod(NotEqual::class, 'assertNotEquals', 'assertEquals'), new BinaryOpWithAssertMethod(Greater::class, 'assertGreaterThan', 'assertLessThanOrEqual'), new BinaryOpWithAssertMethod(Smaller::class, 'assertLessThan', 'assertGreaterThanOrEqual'), new BinaryOpWithAssertMethod(GreaterOrEqual::class, 'assertGreaterThanOrEqual', 'assertLessThan'), new BinaryOpWithAssertMethod(SmallerOrEqual::class, 'assertLessThanOrEqual', 'assertGreaterThan')];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns comparison operations to their method name alternatives in PHPUnit TestCase', [new CodeSample('$this->assertTrue($foo === $bar, "message");', '$this->assertSame($bar, $foo, "message");'), new CodeSample('$this->assertFalse($foo >= $bar, "message");', '$this->assertLessThanOrEqual($bar, $foo, "message");')]);
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
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['assertTrue', 'assertFalse'])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArgumentValue = $node->getArgs()[0]->value;
        if (!$firstArgumentValue instanceof BinaryOp) {
            return null;
        }
        return $this->processCallWithBinaryOp($node, $firstArgumentValue);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    private function processCallWithBinaryOp($node, BinaryOp $binaryOp): ?Node
    {
        $binaryOpClass = $binaryOp::class;
        foreach ($this->binaryOpWithAssertMethods as $binaryOpWithAssertMethod) {
            if ($binaryOpClass !== $binaryOpWithAssertMethod->getBinaryOpClass()) {
                continue;
            }
            $this->identifierManipulator->renameNodeWithMap($node, ['assertTrue' => $binaryOpWithAssertMethod->getAssetMethodName(), 'assertFalse' => $binaryOpWithAssertMethod->getNotAssertMethodName()]);
            $shouldKeepOrder = $binaryOp instanceof Greater || $binaryOp instanceof GreaterOrEqual || $binaryOp instanceof Smaller || $binaryOp instanceof SmallerOrEqual;
            $this->changeArgumentsOrder($node, $shouldKeepOrder);
            return $node;
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    private function changeArgumentsOrder($node, bool $shouldKeepOrder): void
    {
        $oldArguments = $node->getArgs();
        /** @var BinaryOp $expression */
        $expression = $oldArguments[0]->value;
        if ($this->isConstantValue($expression->left) && !$shouldKeepOrder) {
            $firstArgument = new Arg($expression->left);
            $secondArgument = new Arg($expression->right);
        } else {
            $firstArgument = new Arg($expression->right);
            $secondArgument = new Arg($expression->left);
        }
        unset($oldArguments[0]);
        $newArgs = [$firstArgument, $secondArgument];
        $node->args = array_merge($newArgs, $oldArguments);
    }
    private function isConstantValue(Expr $expr): bool
    {
        $staticType = $this->nodeTypeResolver->getType($expr);
        if ($staticType instanceof ConstantScalarType) {
            return \true;
        }
        return $staticType instanceof ConstantArrayType;
    }
}
