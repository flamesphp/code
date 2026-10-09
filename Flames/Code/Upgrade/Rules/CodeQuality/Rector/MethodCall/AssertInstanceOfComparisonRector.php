<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\IdentifierManipulator;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertInstanceOfComparisonRectorTest
 */
final class AssertInstanceOfComparisonRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const array RENAME_METHODS_MAP = ['assertTrue' => 'assertInstanceOf', 'assertFalse' => 'assertNotInstanceOf'];
    public function __construct(private readonly IdentifierManipulator $identifierManipulator, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns instanceof comparisons to their method name alternatives in PHPUnit TestCase', [new CodeSample('$this->assertTrue($foo instanceof Foo, "message");', '$this->assertInstanceOf("Foo", $foo, "message");'), new CodeSample('$this->assertFalse($foo instanceof Foo, "message");', '$this->assertNotInstanceOf("Foo", $foo, "message");'), new CodeSample('$this->assertNotEquals(SomeInstance::class, get_class($value));', '$this->assertNotInstanceOf(SomeInstance::class, $value);')]);
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
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if ($this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, ['assertSame', 'assertNotSame', 'assertEquals', 'assertNotEquals'])) {
            return $this->refactorGetClass($node);
        }
        $oldMethodNames = array_keys(self::RENAME_METHODS_MAP);
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, $oldMethodNames)) {
            return null;
        }
        $firstArgumentValue = $node->getArgs()[0]->value;
        if (!$firstArgumentValue instanceof Instanceof_) {
            return null;
        }
        $this->identifierManipulator->renameNodeWithMap($node, self::RENAME_METHODS_MAP);
        $this->changeArgumentsOrder($node);
        return $node;
    }
    /**
     * @param MethodCall|StaticCall $node
     */
    private function refactorGetClass(Node $node): ?Node
    {
        // we need 2 args
        if (!isset($node->args[1])) {
            return null;
        }
        $secondArgument = $node->getArgs()[1];
        $secondArgumentValue = $secondArgument->value;
        if ($secondArgumentValue instanceof FuncCall && $this->isName($secondArgumentValue->name, 'get_class')) {
            $node->args[1] = $secondArgumentValue->getArgs()[0];
            if ($this->isNames($node->name, ['assertSame', 'assertEquals'])) {
                $node->name = new Identifier('assertInstanceOf');
            } elseif ($this->isNames($node->name, ['assertNotSame', 'assertNotEquals'])) {
                $node->name = new Identifier('assertNotInstanceOf');
            }
            return $node;
        }
        return null;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    private function changeArgumentsOrder($node): void
    {
        $oldArguments = $node->getArgs();
        /** @var Instanceof_ $comparison */
        $comparison = $oldArguments[0]->value;
        $argument = $comparison->expr;
        unset($oldArguments[0]);
        if ($comparison->class instanceof Variable) {
            $firstArgument = new Arg($comparison->class);
        } else {
            $className = $this->getName($comparison->class);
            if ($className === null) {
                throw new ShouldNotHappenException();
            }
            $firstArgument = new Arg($this->nodeFactory->createClassConstReference($className));
        }
        $node->args = array_merge([$firstArgument, new Arg($argument)], $oldArguments);
    }
}
