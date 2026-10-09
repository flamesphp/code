<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Case_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
final readonly class SwitchAnalyzer
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private TypeFactory $typeFactory)
    {
    }
    /**
     * @param Case_[] $cases
     */
    public function hasDifferentTypeCases(array $cases, Expr $expr): bool
    {
        $types = [];
        foreach ($cases as $case) {
            if ($case->cond instanceof Expr) {
                $types[] = $this->nodeTypeResolver->getType($case->cond);
            }
        }
        if ($types === []) {
            return \false;
        }
        $uniqueTypes = $this->typeFactory->uniquateTypes($types);
        $countUniqueTypes = count($uniqueTypes);
        if ($countUniqueTypes === 1 && $uniqueTypes[0]->isInteger()->yes()) {
            $switchCondType = $this->nodeTypeResolver->getType($expr);
            if (!$switchCondType instanceof MixedType && $switchCondType->isString()->maybe()) {
                return \true;
            }
        }
        return $countUniqueTypes > 1;
    }
    public function hasEachCaseBreak(Switch_ $switch): bool
    {
        $totalCases = count($switch->cases);
        if ($totalCases === 1) {
            return $this->containsCaseReturn($switch->cases[0]);
        }
        foreach ($switch->cases as $key => $case) {
            if ($key === $totalCases - 1) {
                return \true;
            }
            if ($this->hasBreakOrReturnOrEmpty($case)) {
                continue;
            }
            return \false;
        }
        return \true;
    }
    public function hasEachCaseSingleStmt(Switch_ $switch): bool
    {
        foreach ($switch->cases as $case) {
            if (!$case->cond instanceof Expr) {
                continue;
            }
            $stmtsWithoutBreak = array_filter($case->stmts, static fn(Node $node): bool => !$node instanceof Break_);
            if (count($stmtsWithoutBreak) !== 1) {
                return \false;
            }
        }
        return \true;
    }
    public function hasDefaultSingleStmt(Switch_ $switch): bool
    {
        foreach ($switch->cases as $case) {
            if (!$case->cond instanceof Expr) {
                $stmtsWithoutBreak = array_filter($case->stmts, static fn(Node $node): bool => !$node instanceof Break_);
                return count($stmtsWithoutBreak) === 1;
            }
        }
        return \false;
    }
    private function hasBreakOrReturnOrEmpty(Case_ $case): bool
    {
        if ($case->stmts === []) {
            return \true;
        }
        foreach ($case->stmts as $caseStmt) {
            if ($caseStmt instanceof Break_) {
                return \true;
            }
            if ($caseStmt instanceof Return_) {
                return \true;
            }
        }
        return \false;
    }
    private function containsCaseReturn(Case_ $case): bool
    {
        $found = array_any($case->stmts, fn($stmt) => $stmt instanceof Return_);
        return $found;
    }
}
