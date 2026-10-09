<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\Expression;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Match_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Throw_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\MatchArm;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Case_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Echo_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rules\Php72\NodeFactory\AnonymousFunctionFactory;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/match_expression_v2
 *
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\Expression\DowngradeMatchToSwitchRectorTest
 */
final class DowngradeMatchToSwitchRector extends AbstractRector
{
    public function __construct(private readonly AnonymousFunctionFactory $anonymousFunctionFactory, private readonly VariableNaming $variableNaming)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade match() to switch()', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $message = match ($statusCode) {
            200, 300 => null,
            400 => 'not found',
            default => 'unknown status code',
        };
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        switch ($statusCode) {
            case 200:
            case 300:
                $message = null;
                break;
            case 400:
                $message = 'not found';
                break;
            default:
                $message = 'unknown status code';
                break;
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
        return [Echo_::class, Expression::class, Return_::class];
    }
    /**
     * @param Echo_|Expression|Return_ $node
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node|mixed[]
     */
    public function refactor(Node $node)
    {
        /** @var Match_|null $match */
        $match = null;
        $hasChanged = \false;
        $scope = ScopeFetcher::fetch($node);
        if ($node instanceof Expression && $node->expr instanceof Assign && $node->expr->var instanceof ArrayDimFetch && $node->expr->var->var instanceof ArrayDimFetch && $node->expr->var->var->dim instanceof Match_) {
            $matchVariable = new Variable($this->variableNaming->createCountedValueName('match', $scope));
            $expression = new Expression(new Assign($matchVariable, $node->expr->var->var->dim));
            $expression->setAttribute(AttributeKey::SCOPE, $scope);
            $refactored = $this->refactor($expression);
            if ($refactored === null) {
                return null;
            }
            $node->expr->var->var->dim = $matchVariable;
            $stmts = is_array($refactored) ? $refactored : [$refactored];
            return array_merge($stmts, [$node]);
        }
        $this->traverseNodesWithCallable($node, function (Node $subNode) use ($node, &$match, &$hasChanged, $scope) {
            if (($subNode instanceof ArrayItem || $subNode instanceof Arg) && $subNode->value instanceof Match_ && $this->isEqualScope($subNode->value, $scope)) {
                $switchCases = $this->createSwitchCasesFromMatchArms($node, $subNode->value, \true);
                $switch = new Switch_($subNode->value->cond, $switchCases);
                $subNode->value = new FuncCall($this->anonymousFunctionFactory->create([], [$switch], null));
                $hasChanged = \true;
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof Arg && $subNode->value instanceof ArrowFunction && $subNode->value->expr instanceof Match_) {
                $refactoredNode = $this->refactorInArrowFunction($subNode, $subNode->value, $subNode->value->expr);
                if ($refactoredNode instanceof Node) {
                    $hasChanged = \true;
                }
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof Assign && $subNode->expr instanceof ArrowFunction && $subNode->expr->expr instanceof Match_) {
                $refactoredNode = $this->refactorInArrowFunction($subNode, $subNode->expr, $subNode->expr->expr);
                if ($refactoredNode instanceof Node) {
                    $hasChanged = \true;
                }
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof Expression && $subNode->expr instanceof ArrowFunction && $subNode->expr->expr instanceof Match_) {
                $refactoredNode = $this->refactorInArrowFunction($subNode, $subNode->expr, $subNode->expr->expr);
                if ($refactoredNode instanceof Node) {
                    $hasChanged = \true;
                }
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof Return_ && $subNode->expr instanceof ArrowFunction && $subNode->expr->expr instanceof Match_) {
                $refactoredNode = $this->refactorInArrowFunction($subNode, $subNode->expr, $subNode->expr->expr);
                if ($refactoredNode instanceof Node) {
                    $hasChanged = \true;
                }
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof FuncCall && $subNode->name instanceof ArrowFunction && $subNode->name->expr instanceof Match_) {
                $refactoredNode = $this->refactorInArrowFunction($subNode, $subNode->name, $subNode->name->expr);
                if ($refactoredNode instanceof Node) {
                    $hasChanged = \true;
                }
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof Match_) {
                $match = $subNode;
                return NodeVisitor::STOP_TRAVERSAL;
            }
        });
        if ($hasChanged) {
            return $node;
        }
        if (!$match instanceof Match_) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        if (!$this->isEqualScope($match, $scope)) {
            return null;
        }
        $switchCases = $this->createSwitchCasesFromMatchArms($node, $match);
        return new Switch_($match->cond, $switchCases);
    }
    private function isEqualScope(Match_ $match, ?Scope $containerScope): bool
    {
        $matchScope = $match->getAttribute(AttributeKey::SCOPE);
        if (!$matchScope instanceof Scope) {
            return \false;
        }
        if (!$containerScope instanceof Scope) {
            return \false;
        }
        return $matchScope->getParentScope() === $containerScope->getParentScope();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_ $containerArrowFunction
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_|null
     */
    private function refactorInArrowFunction($containerArrowFunction, ArrowFunction $arrowFunction, Match_ $match)
    {
        $containerArrowFunctionScope = $containerArrowFunction->getAttribute(AttributeKey::SCOPE);
        if (!$this->isEqualScope($match, $containerArrowFunctionScope)) {
            return null;
        }
        /**
         * Yes, Pass Match_ object itself to Return_
         * Let the Rule revisit the Match_ after the ArrowFunction converted to Closure_
         */
        $stmts = [new Return_($match)];
        $closure = $this->anonymousFunctionFactory->create($arrowFunction->params, $stmts, $arrowFunction->returnType, $arrowFunction->static);
        if ($containerArrowFunction instanceof Arg && $containerArrowFunction->value === $arrowFunction) {
            $containerArrowFunction->value = $closure;
            return $containerArrowFunction;
        }
        if (($containerArrowFunction instanceof Assign || $containerArrowFunction instanceof Expression || $containerArrowFunction instanceof Return_) && $containerArrowFunction->expr === $arrowFunction) {
            $containerArrowFunction->expr = $closure;
            return $containerArrowFunction;
        }
        if ($containerArrowFunction instanceof FuncCall && $containerArrowFunction->name === $arrowFunction) {
            $containerArrowFunction->name = $closure;
            return $containerArrowFunction;
        }
        return null;
    }
    /**
     * @return Case_[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Echo_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_ $node
     */
    private function createSwitchCasesFromMatchArms($node, Match_ $match, bool $isInsideArrayItem = \false): array
    {
        $switchCases = [];
        foreach ($match->arms as $matchArm) {
            if (count((array) $matchArm->conds) > 1) {
                $lastCase = null;
                foreach ((array) $matchArm->conds as $matchArmCond) {
                    $lastCase = new Case_($matchArmCond);
                    $switchCases[] = $lastCase;
                }
                /** @var Case_ $lastCase */
                $lastCase->stmts = $this->createSwitchStmts($node, $matchArm, $isInsideArrayItem);
            } else {
                $stmts = $this->createSwitchStmts($node, $matchArm, $isInsideArrayItem);
                $switchCases[] = new Case_($matchArm->conds[0] ?? null, $stmts);
            }
        }
        return $switchCases;
    }
    /**
     * @return Stmt[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Echo_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_ $node
     */
    private function createSwitchStmts($node, MatchArm $matchArm, bool $isInsideArrayItem): array
    {
        $stmts = [];
        if ($isInsideArrayItem) {
            $stmts[] = new Return_($matchArm->body);
        } elseif ($matchArm->body instanceof Throw_) {
            $stmts[] = new Expression($matchArm->body);
        } elseif ($node instanceof Return_) {
            if ($node->expr instanceof BinaryOp) {
                $stmts[] = $this->replicateBinaryOp($node->expr, $matchArm->body);
            } else {
                $stmts[] = new Return_($matchArm->body);
            }
        } elseif ($node instanceof Echo_) {
            $stmts[] = new Echo_([$matchArm->body]);
            $stmts[] = new Break_();
        } elseif ($node->expr instanceof Assign) {
            $stmts[] = new Expression(new Assign($node->expr->var, $matchArm->body));
            $stmts[] = new Break_();
        } elseif ($node->expr instanceof Match_) {
            $stmts[] = new Expression($matchArm->body);
            $stmts[] = new Break_();
        } elseif ($node->expr instanceof CallLike) {
            /** @var FuncCall|MethodCall|New_|NullsafeMethodCall|StaticCall $call */
            $call = clone $node->expr;
            $call->args = [new Arg($matchArm->body)];
            $stmts[] = new Expression($call);
            $stmts[] = new Break_();
        }
        return $stmts;
    }
    private function replicateBinaryOp(BinaryOp $binaryOp, Expr $expr): Return_
    {
        $newExpr = clone $binaryOp;
        // remove the match statement from the binary operation
        $this->traverseNodesWithCallable($newExpr, static function (Node $node) use ($expr): ?Expr {
            if ($node instanceof Match_) {
                return $expr;
            }
            return null;
        });
        return new Return_($newExpr);
    }
}
