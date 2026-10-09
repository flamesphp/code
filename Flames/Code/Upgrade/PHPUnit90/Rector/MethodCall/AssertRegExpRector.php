<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit90\Rector\MethodCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeManipulator\StmtsManipulator;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\PHPUnit90\Rector\MethodCall\AssertRegExpRectorTest
 */
final class AssertRegExpRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    /**
     * assertMatchesRegularExpression() was added in PHPUnit 9.1
     */
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('phpunit/phpunit', '>=9.1');
    }
    private const string ASSERT_SAME = 'assertSame';
    private const string ASSERT_EQUALS = 'assertEquals';
    private const string ASSERT_NOT_SAME = 'assertNotSame';
    private const string ASSERT_NOT_EQUALS = 'assertNotEquals';
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ValueResolver $valueResolver, private readonly StmtsManipulator $stmtsManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Turns `preg_match` comparisons to their method name alternatives in PHPUnit TestCase', [new CodeSample('$this->assertSame(1, preg_match("/^Message for ".*"\.$/", $string), $message);', '$this->assertMatchesRegularExpression("/^Message for ".*"\.$/", $string, $message);'), new CodeSample('$this->assertEquals(false, preg_match("/^Message for ".*"\.$/", $string), $message);', '$this->assertDoesNotMatchRegularExpression("/^Message for ".*"\.$/", $string, $message);')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return NodeGroup::STMTS_AWARE;
    }
    /**
     * @param StmtsAware $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof MethodCall && !$stmt->expr instanceof StaticCall) {
                continue;
            }
            if (!$this->testsNodeAnalyzer->isPHPUnitMethodCallNames($stmt->expr, [self::ASSERT_SAME, self::ASSERT_EQUALS, self::ASSERT_NOT_SAME, self::ASSERT_NOT_EQUALS])) {
                continue;
            }
            if ($stmt->expr->isFirstClassCallable()) {
                continue;
            }
            /** @var FuncCall|Node $secondArgumentValue */
            $secondArgumentValue = $stmt->expr->getArgs()[1]->value;
            if (!$secondArgumentValue instanceof FuncCall) {
                continue;
            }
            if (!$this->isName($secondArgumentValue, 'preg_match')) {
                continue;
            }
            if ($secondArgumentValue->isFirstClassCallable()) {
                continue;
            }
            $oldMethodName = $this->getName($stmt->expr->name);
            if ($oldMethodName === null) {
                continue;
            }
            $args = $secondArgumentValue->getArgs();
            if (isset($args[2]) && $args[2]->value instanceof Variable && $this->stmtsManipulator->isVariableUsedInNextStmt($node, $key + 1, (string) $this->getName($args[2]->value))) {
                continue;
            }
            $oldFirstArgument = $stmt->expr->getArgs()[0]->value;
            $oldCondition = $this->resolveOldCondition($oldFirstArgument);
            $this->renameMethod($stmt->expr, $oldMethodName, $oldCondition);
            $this->moveFunctionArgumentsUp($stmt->expr);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function resolveOldCondition(Expr $expr): int
    {
        if ($expr instanceof Int_) {
            return $expr->value;
        }
        if ($expr instanceof ConstFetch) {
            return $this->valueResolver->isTrue($expr) ? 1 : 0;
        }
        throw new ShouldNotHappenException();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    private function renameMethod($node, string $oldMethodName, int $oldCondition): void
    {
        if (in_array($oldMethodName, [self::ASSERT_SAME, self::ASSERT_EQUALS], \true) && $oldCondition === 1 || in_array($oldMethodName, [self::ASSERT_NOT_SAME, self::ASSERT_NOT_EQUALS], \true) && $oldCondition === 0) {
            $node->name = new Identifier('assertMatchesRegularExpression');
        }
        if (in_array($oldMethodName, [self::ASSERT_SAME, self::ASSERT_EQUALS], \true) && $oldCondition === 0 || in_array($oldMethodName, [self::ASSERT_NOT_SAME, self::ASSERT_NOT_EQUALS], \true) && $oldCondition === 1) {
            $node->name = new Identifier('assertDoesNotMatchRegularExpression');
        }
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall $node
     */
    private function moveFunctionArgumentsUp($node): void
    {
        $oldArguments = $node->getArgs();
        /** @var FuncCall $pregMatchFunction */
        $pregMatchFunction = $oldArguments[1]->value;
        $regex = $pregMatchFunction->getArgs()[0];
        $variable = $pregMatchFunction->getArgs()[1];
        unset($oldArguments[0], $oldArguments[1]);
        $node->args = array_merge([$regex, $variable], $oldArguments);
    }
}
