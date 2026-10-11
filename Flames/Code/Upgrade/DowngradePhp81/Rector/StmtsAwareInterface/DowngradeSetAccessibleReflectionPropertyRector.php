<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\Rector\StmtsAwareInterface;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Smaller;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://phpbackend.com/blog/post/php-8-1-accessing-private-protected-properties-methods-via-reflection-api-is-now-allowed-without-calling-setAccessible
 *
 * @see \Flames\Code\Upgrade\DowngradePhp81\Rector\StmtsAwareInterface\DowngradeSetAccessibleReflectionPropertyRectorTest
 */
final class DowngradeSetAccessibleReflectionPropertyRector extends AbstractRector
{
    public function __construct(private readonly VariableNaming $variableNaming)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add setAccessible() on ReflectionProperty to allow reading private properties in PHP 8.0-', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($object)
    {
        $reflectionProperty = new ReflectionProperty($object, 'bar');

        return $reflectionProperty->getValue($object);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($object)
    {
        $reflectionProperty = new ReflectionProperty($object, 'bar');
        if (PHP_VERSION_ID < 80100) {
            $reflectionProperty->setAccessible(true);
        }

        return $reflectionProperty->getValue($object);
    }
}
CODE_SAMPLE
), new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($object)
    {
        $reflectionObject = new ReflectionObject($object);
        foreach ($reflectionObject->getProperties() as $reflectionProperty) {
            echo $reflectionProperty->getValue($object);
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($object)
    {
        $reflectionObject = new ReflectionObject($object);
        foreach ($reflectionObject->getProperties() as $reflectionProperty) {
            if (PHP_VERSION_ID < 80100) {
                $reflectionProperty->setAccessible(true);
            }
            echo $reflectionProperty->getValue($object);
        }
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
            if ($stmt instanceof Foreach_) {
                if ($this->refactorForeach($stmt)) {
                    $hasChanged = \true;
                }
                continue;
            }
            if (!$stmt instanceof Expression && !$stmt instanceof Return_) {
                continue;
            }
            if ($stmt instanceof Expression) {
                if (!$stmt->expr instanceof Assign) {
                    continue;
                }
                $assign = $stmt->expr;
                if (!$assign->expr instanceof New_) {
                    continue;
                }
                $new = $assign->expr;
                $variable = $assign->var;
            } else {
                if (!$stmt->expr instanceof New_) {
                    continue;
                }
                $new = $stmt->expr;
                $scope = ScopeFetcher::fetch($stmt);
                $variable = new Variable($this->variableNaming->createCountedValueName('reflection', $scope));
            }
            if (!$this->isNames($new->class, ['ReflectionProperty', 'ReflectionMethod'])) {
                continue;
            }
            if ($stmt instanceof Expression) {
                // next stmts should be setAccessible() call
                $nextStmt = $node->stmts[$key + 1] ?? null;
                if ($this->isSetAccessibleMethodCall($nextStmt)) {
                    continue;
                }
                if ($this->isSetAccessibleIfMethodCall($nextStmt)) {
                    continue;
                }
                array_splice($node->stmts, $key + 1, 0, [$this->createSetAccessibleExpression($variable)]);
            } else {
                $previousStmts = [new Expression(new Assign($variable, $new)), $this->createSetAccessibleExpression($variable)];
                $stmt->expr = $variable;
                array_splice($node->stmts, $key - 2, 0, $previousStmts);
            }
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function refactorForeach(Foreach_ $foreach): bool
    {
        if (!$foreach->valueVar instanceof Variable) {
            return \false;
        }
        if (!$this->isReflectionMembersCall($foreach->expr)) {
            return \false;
        }
        $firstStmt = $foreach->stmts[0] ?? null;
        if ($this->isSetAccessibleMethodCall($firstStmt) || $this->isSetAccessibleIfMethodCall($firstStmt)) {
            return \false;
        }
        array_unshift($foreach->stmts, $this->createSetAccessibleExpression($foreach->valueVar));
        return \true;
    }
    private function isReflectionMembersCall(Expr $expr): bool
    {
        if (!$expr instanceof MethodCall) {
            return \false;
        }
        if (!$this->isNames($expr->name, ['getProperties', 'getMethods'])) {
            return \false;
        }
        $callerType = $this->nodeTypeResolver->getType($expr->var);
        if (!$callerType instanceof ObjectType) {
            return \false;
        }
        return $callerType->isInstanceOf('ReflectionClass')->yes();
    }
    private function createSetAccessibleExpression(Expr $expr): If_
    {
        $args = [$this->nodeFactory->createArg($this->nodeFactory->createTrue())];
        $setAccessibleMethodCall = $this->nodeFactory->createMethodCall($expr, 'setAccessible', $args);
        return new If_(new Smaller(new ConstFetch(new Name('PHP_VERSION_ID')), new Int_(80100)), ['stmts' => [new Expression($setAccessibleMethodCall)]]);
    }
    private function isSetAccessibleMethodCall(?Stmt $stmt): bool
    {
        if (!$stmt instanceof Expression) {
            return \false;
        }
        if (!$stmt->expr instanceof MethodCall) {
            return \false;
        }
        $methodCall = $stmt->expr;
        return $this->isName($methodCall->name, 'setAccessible');
    }
    private function isSetAccessibleIfMethodCall(?Stmt $stmt): bool
    {
        if (!$stmt instanceof If_) {
            return \false;
        }
        if (!$stmt->cond instanceof Smaller) {
            return \false;
        }
        if (!$stmt->cond->left instanceof ConstFetch || !$this->isName($stmt->cond->left->name, 'PHP_VERSION_ID')) {
            return \false;
        }
        if (!$stmt->cond->right instanceof Int_ || $stmt->cond->right->value !== 80100) {
            return \false;
        }
        if (count($stmt->stmts) !== 1) {
            return \false;
        }
        return $this->isSetAccessibleMethodCall($stmt->stmts[0]);
    }
}
