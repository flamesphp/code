<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Exit_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Throw_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Yield_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Case_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Continue_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Finally_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Goto_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\TryCatch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\NeverFuncCallAnalyzer;
final readonly class SilentVoidResolver
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private ReflectionResolver $reflectionResolver, private NeverFuncCallAnalyzer $neverFuncCallAnalyzer, private ValueResolver $valueResolver, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function hasExclusiveVoid($functionLike): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($functionLike);
        if ($classReflection instanceof ClassReflection && $classReflection->isInterface()) {
            return \false;
        }
        return !(bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($functionLike, function (Node $subNode): bool {
            if ($subNode instanceof Yield_ || $subNode instanceof YieldFrom) {
                return \true;
            }
            return $subNode instanceof Return_ && $subNode->expr instanceof Expr;
        });
    }
    public function hasSilentVoid(FunctionLike $functionLike, bool $withNativeNeverType = \true): bool
    {
        if ($functionLike instanceof ArrowFunction) {
            return \false;
        }
        $stmts = (array) $functionLike->getStmts();
        return !$this->hasStmtsAlwaysReturnOrExit($stmts, $withNativeNeverType);
    }
    /**
     * @param Stmt[]|Expression[] $stmts
     */
    private function hasStmtsAlwaysReturnOrExit(array $stmts, bool $withNativeNeverType): bool
    {
        foreach ($stmts as $stmt) {
            if ($this->neverFuncCallAnalyzer->isWithNeverTypeExpr($stmt, $withNativeNeverType)) {
                return \true;
            }
            if ($this->isStopped($stmt)) {
                return \true;
            }
            // has switch with always return
            if ($stmt instanceof Switch_ && $this->isSwitchWithAlwaysReturnOrExit($stmt, $withNativeNeverType)) {
                return \true;
            }
            if ($stmt instanceof TryCatch && $this->isTryCatchAlwaysReturnOrExit($stmt, $withNativeNeverType)) {
                return \true;
            }
            if ($this->isIfReturn($stmt, $withNativeNeverType)) {
                return \true;
            }
            if (!$this->isDoOrWhileWithAlwaysReturnOrExit($stmt, $withNativeNeverType)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_ $node
     */
    private function isFoundLoopControl($node): bool
    {
        $isFoundLoopControl = \false;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($node->stmts, static function (Node $subNode) use (&$isFoundLoopControl) {
            if ($subNode instanceof Class_ || $subNode instanceof Function_ || $subNode instanceof Closure) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($subNode instanceof Break_ || $subNode instanceof Continue_ || $subNode instanceof Goto_) {
                $isFoundLoopControl = \true;
                return NodeVisitor::STOP_TRAVERSAL;
            }
        });
        return $isFoundLoopControl;
    }
    private function isDoOrWhileWithAlwaysReturnOrExit(Stmt $stmt, bool $withNativeNeverType): bool
    {
        if (!$stmt instanceof Do_ && !$stmt instanceof While_) {
            return \false;
        }
        if ($this->valueResolver->isTrue($stmt->cond)) {
            return !$this->isFoundLoopControl($stmt);
        }
        if (!$this->hasStmtsAlwaysReturnOrExit($stmt->stmts, $withNativeNeverType)) {
            return \false;
        }
        return $stmt instanceof Do_ && !$this->isFoundLoopControl($stmt);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr $stmt
     */
    private function isIfReturn($stmt, bool $withNativeNeverType): bool
    {
        if (!$stmt instanceof If_) {
            return \false;
        }
        foreach ($stmt->elseifs as $elseIf) {
            if (!$this->hasStmtsAlwaysReturnOrExit($elseIf->stmts, $withNativeNeverType)) {
                return \false;
            }
        }
        if (!$stmt->else instanceof Else_) {
            return \false;
        }
        if (!$this->hasStmtsAlwaysReturnOrExit($stmt->stmts, $withNativeNeverType)) {
            return \false;
        }
        return $this->hasStmtsAlwaysReturnOrExit($stmt->else->stmts, $withNativeNeverType);
    }
    private function isStopped(Stmt $stmt): bool
    {
        if ($stmt instanceof Expression) {
            $stmt = $stmt->expr;
        }
        return $stmt instanceof Throw_ || $stmt instanceof Exit_ || $stmt instanceof Return_ && $stmt->expr instanceof Expr || $stmt instanceof Yield_ || $stmt instanceof YieldFrom;
    }
    private function isSwitchWithAlwaysReturnOrExit(Switch_ $switch, bool $withNativeNeverType): bool
    {
        $hasDefault = \false;
        foreach ($switch->cases as $case) {
            if (!$case->cond instanceof Expr) {
                $hasDefault = $case->stmts !== [];
                break;
            }
        }
        if (!$hasDefault) {
            return \false;
        }
        $casesWithReturnOrExitCount = $this->resolveReturnOrExitCount($switch, $withNativeNeverType);
        $cases = array_filter($switch->cases, static fn(Case_ $case): bool => $case->stmts !== []);
        // has same amount of first return or exit nodes as switches
        return count($cases) === $casesWithReturnOrExitCount;
    }
    private function isTryCatchAlwaysReturnOrExit(TryCatch $tryCatch, bool $withNativeNeverType): bool
    {
        $hasReturnOrExitInFinally = $tryCatch->finally instanceof Finally_ && $this->hasStmtsAlwaysReturnOrExit($tryCatch->finally->stmts, $withNativeNeverType);
        if (!$this->hasStmtsAlwaysReturnOrExit($tryCatch->stmts, $withNativeNeverType)) {
            return $hasReturnOrExitInFinally;
        }
        foreach ($tryCatch->catches as $catch) {
            if ($this->hasStmtsAlwaysReturnOrExit($catch->stmts, $withNativeNeverType)) {
                continue;
            }
            if ($hasReturnOrExitInFinally) {
                continue;
            }
            return \false;
        }
        return \true;
    }
    private function resolveReturnOrExitCount(Switch_ $switch, bool $withNativeNeverType): int
    {
        $casesWithReturnCount = 0;
        foreach ($switch->cases as $case) {
            if ($this->hasStmtsAlwaysReturnOrExit($case->stmts, $withNativeNeverType)) {
                ++$casesWithReturnCount;
            }
        }
        return $casesWithReturnCount;
    }
}
