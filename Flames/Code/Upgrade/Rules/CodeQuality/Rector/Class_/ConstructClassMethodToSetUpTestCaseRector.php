<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\NodeAnalyzer\ClassAnalyzer;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\SetUpMethodDecorator;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://github.com/sebastianbergmann/phpunit/issues/3975#issuecomment-562584609
 *
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\ConstructClassMethodToSetUpTestCaseRectorTest
 */
final class ConstructClassMethodToSetUpTestCaseRector extends AbstractRector
{
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly ClassAnalyzer $classAnalyzer, private readonly VisibilityManipulator $visibilityManipulator, private readonly SetUpMethodDecorator $setUpMethodDecorator, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change __construct() method in tests of `PHPUnit\Framework\TestCase` to setUp(), to prevent dangerous override', [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private $someValue;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        $this->someValue = 1000;
        parent::__construct($name, $data, $dataName);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    private $someValue;

    protected function setUp()
    {
        parent::setUp();

        $this->someValue = 1000;
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
        if ($this->shouldSkipClass($node)) {
            return null;
        }
        foreach ($node->stmts as $stmtKey => $classStmt) {
            if (!$classStmt instanceof ClassMethod) {
                continue;
            }
            if (!$this->isName($classStmt->name, MethodName::CONSTRUCT)) {
                continue;
            }
            if ($this->shouldSkip($node, $classStmt)) {
                return null;
            }
            $addedStmts = $this->resolveStmtsToAddToSetUp($classStmt);
            $setUpClassMethod = $node->getMethod(MethodName::SET_UP);
            if (!$setUpClassMethod instanceof ClassMethod) {
                // no setUp() method yet, rename it to setUp :)
                $classStmt->name = new Identifier(MethodName::SET_UP);
                $classStmt->params = [];
                $classStmt->stmts = $addedStmts;
                $this->setUpMethodDecorator->decorate($classStmt);
                $this->visibilityManipulator->makeProtected($classStmt);
                return $node;
            }
            // remove constructor and add stmts to already existing setUp() method
            unset($node->stmts[$stmtKey]);
            $setUpClassMethod->stmts = array_merge((array) $setUpClassMethod->stmts, $addedStmts);
            return $node;
        }
        return null;
    }
    private function shouldSkip(Class_ $class, ClassMethod $classMethod): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($class);
        if (!$classReflection instanceof ClassReflection) {
            return \true;
        }
        $currentParent = current($classReflection->getParents());
        if (!$currentParent instanceof ClassReflection) {
            return \true;
        }
        if ($currentParent->getName() !== PHPUnitClassName::TEST_CASE) {
            return \true;
        }
        $paramNames = [];
        foreach ($classMethod->params as $param) {
            $paramNames[] = $this->getName($param);
        }
        $isFoundParamUsed = \false;
        $this->traverseNodesWithCallable((array) $classMethod->stmts, function (Node $subNode) use ($paramNames, &$isFoundParamUsed): ?int {
            if ($subNode instanceof StaticCall && $this->isName($subNode->name, MethodName::CONSTRUCT)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }
            if ($subNode instanceof Variable && $this->isNames($subNode, $paramNames)) {
                $isFoundParamUsed = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
            return null;
        });
        return $isFoundParamUsed;
    }
    /**
     * @return Stmt[]
     */
    private function resolveStmtsToAddToSetUp(ClassMethod $constructClassMethod): array
    {
        $constructorStmts = (array) $constructClassMethod->stmts;
        // remove parent call
        foreach ($constructorStmts as $key => $constructorStmt) {
            if ($constructorStmt instanceof Expression) {
                $constructorStmt = clone $constructorStmt->expr;
            }
            if (!$this->isParentCallNamed($constructorStmt, MethodName::CONSTRUCT)) {
                continue;
            }
            unset($constructorStmts[$key]);
        }
        return $constructorStmts;
    }
    private function isParentCallNamed(Node $node, string $desiredMethodName): bool
    {
        if (!$node instanceof StaticCall) {
            return \false;
        }
        if ($node->class instanceof Expr) {
            return \false;
        }
        if (!$this->isName($node->class, 'parent')) {
            return \false;
        }
        if ($node->name instanceof Expr) {
            return \false;
        }
        return $this->isName($node->name, $desiredMethodName);
    }
    private function shouldSkipClass(Class_ $class): bool
    {
        $className = $this->getName($class);
        // probably helper class with access to protected methods like createMock()
        if (!str_ends_with((string) $className, 'Test') && !str_ends_with((string) $className, 'TestCase')) {
            return \true;
        }
        return $this->classAnalyzer->isAnonymousClass($class);
    }
}
