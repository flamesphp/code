<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\AssertHasKeyMatcher;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFactory\AssertArrayHasKeyCallFactory;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableAndDimFetch;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassMethod\AddKeysExistsAssertForKeyUseRectorTest
 */
final class AddKeysExistsAssertForKeyUseRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ValueResolver $valueResolver, private readonly AssertHasKeyMatcher $assertHasKeyMatcher, private readonly AssertArrayHasKeyCallFactory $assertArrayHasKeyCallFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add assertArrayHasKey() call for array access with string key, that was not validated before', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $data = $this->getData();
        $this->assertSame('result', $data['key']);
    }

    private function getData(): array
    {
        // return various data
        return [];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    public function test()
    {
        $data = $this->getData();
        $this->assertArrayHasKey('key', $data);
        $this->assertSame('result', $data['key']);
    }

    private function getData(): array
    {
        // return various data
        return [];
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?ClassMethod
    {
        if (!$this->testsNodeAnalyzer->isTestClassMethod($node)) {
            return null;
        }
        if ($node->stmts === [] || $node->stmts === null || count($node->stmts) < 2) {
            return null;
        }
        $knownVariableDimFetches = [];
        $next = 0;
        $hasChanged = \false;
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            $variableAndDimFetch = $this->assertHasKeyMatcher->match($stmt);
            if ($variableAndDimFetch instanceof VariableAndDimFetch) {
                $knownVariableDimFetches[] = $variableAndDimFetch;
                continue;
            }
            if (!$this->testsNodeAnalyzer->isAssertMethodCallName($stmt->expr, 'assertSame')) {
                continue;
            }
            /** @var StaticCall|MethodCall $call */
            $call = $stmt->expr;
            $assertedArg = $call->getArgs()[1];
            $assertedExpr = $assertedArg->value;
            if (!$assertedExpr instanceof ArrayDimFetch) {
                continue;
            }
            if (!$assertedExpr->var instanceof Variable) {
                continue;
            }
            if (!$assertedExpr->dim instanceof Expr) {
                continue;
            }
            $dimFetchVariableName = $this->getName($assertedExpr->var);
            if ($dimFetchVariableName === null) {
                continue;
            }
            // already known dim, lets skip
            if ($this->isKnownDimFetch($knownVariableDimFetches, $dimFetchVariableName, $assertedExpr->dim)) {
                continue;
            }
            $scope = ScopeFetcher::fetch($node);
            $callExpression = $this->assertArrayHasKeyCallFactory->create($assertedExpr->var, $assertedExpr->dim, $scope);
            array_splice($node->stmts, $key + $next, 0, [$callExpression]);
            ++$next;
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param VariableAndDimFetch[] $knownVariableDimFetches
     */
    private function isKnownDimFetch(array $knownVariableDimFetches, string $dimFetchVariableName, Expr $dimExpr): bool
    {
        foreach ($knownVariableDimFetches as $knownVariableDimFetch) {
            if (!$this->isName($knownVariableDimFetch->getVariable(), $dimFetchVariableName)) {
                continue;
            }
            $dimExprValue = $this->valueResolver->getValue($dimExpr);
            if ($this->valueResolver->isValue($knownVariableDimFetch->getDimFetchExpr(), $dimExprValue)) {
                return \true;
            }
        }
        return \false;
    }
}
