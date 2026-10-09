<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser\AssertMethodAnalyzer;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRectorTest
 */
final class PreferPHPUnitThisCallRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly AssertMethodAnalyzer $assertMethodAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes PHPUnit calls from self::assert*() to $this->assert*()', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function run()
    {
        self::assertEquals('expected', $result);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeClass extends TestCase
{
    public function run()
    {
        $this->assertEquals('expected', $result);
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $hasChanged = \false;
        $this->traverseNodesWithCallable($node, function (Node $node) use (&$hasChanged) {
            $isInsideStaticFunctionLike = $node instanceof ClassMethod && $node->isStatic() || ($node instanceof Closure || $node instanceof ArrowFunction) && $node->static;
            if ($isInsideStaticFunctionLike) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof StaticCall) {
                return null;
            }
            if ($node->isFirstClassCallable()) {
                return null;
            }
            if (!$this->assertMethodAnalyzer->detectTestCaseCall($node)) {
                return null;
            }
            $methodName = $this->getName($node->name);
            if ($methodName === null) {
                return null;
            }
            $hasChanged = \true;
            return $this->nodeFactory->createMethodCall('this', $methodName, $node->getArgs());
        });
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
