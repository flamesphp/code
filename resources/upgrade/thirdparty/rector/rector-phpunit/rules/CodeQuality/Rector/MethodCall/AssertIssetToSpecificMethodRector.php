<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\PHPUnit\Enum\AssertMethod;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\IdentifierManipulator;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\MethodCall\AssertIssetToSpecificMethodRectorTest
 */
final class AssertIssetToSpecificMethodRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const array SUPER_GLOBAL_VARIABLE_NAMES = ['GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV'];
    public function __construct(private readonly IdentifierManipulator $identifierManipulator, private readonly TestsNodeAnalyzer $testsNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns assertTrue() + isset() comparisons to more precise assertArrayHasKey() method', [new CodeSample('$this->assertTrue(isset($anything["foo"]), "message");', '$this->assertArrayHasKey("foo", $anything, "message");')]);
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
        if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($node, [AssertMethod::ASSERT_TRUE, AssertMethod::ASSERT_FALSE])) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $firstArg = $node->getArgs()[0];
        $firstArgumentValue = $firstArg->value;
        // is property access
        if (!$firstArgumentValue instanceof Isset_) {
            return null;
        }
        $issetExpr = $firstArgumentValue->vars[0];
        if (!$issetExpr instanceof ArrayDimFetch) {
            return null;
        }
        // Keep the non-evaluating behavior of isset() for global state. A superglobal
        // may be unavailable or unset, while passing it directly evaluates the variable.
        if ($this->isNames($issetExpr->var, self::SUPER_GLOBAL_VARIABLE_NAMES)) {
            return null;
        }
        // isset() on an ArrayAccess object is not equivalent to assertArrayHasKey():
        // the key may be any type (assertArrayHasKey() requires int|string) and offsetExists()
        // semantics can differ from array_key_exists()
        if ($this->isObjectType($issetExpr->var, new ObjectType('ArrayAccess'))) {
            return null;
        }
        return $this->refactorArrayDimFetchNode($node, $issetExpr);
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall $node
     */
    private function refactorArrayDimFetchNode($node, ArrayDimFetch $arrayDimFetch): Node
    {
        $this->identifierManipulator->renameNodeWithMap($node, [AssertMethod::ASSERT_TRUE => 'assertArrayHasKey', AssertMethod::ASSERT_FALSE => 'assertArrayNotHasKey']);
        $oldArgs = $node->getArgs();
        unset($oldArgs[0]);
        $node->args = array_merge($this->nodeFactory->createArgs([$arrayDimFetch->dim, $arrayDimFetch->var]), $oldArgs);
        return $node;
    }
}
