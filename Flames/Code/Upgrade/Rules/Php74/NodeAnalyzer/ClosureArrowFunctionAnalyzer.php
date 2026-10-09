<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeAnalyzer\CompactFuncCallAnalyzer;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Util\ArrayChecker;
final readonly class ClosureArrowFunctionAnalyzer
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeComparator $nodeComparator, private ArrayChecker $arrayChecker, private PhpDocInfoFactory $phpDocInfoFactory, private CompactFuncCallAnalyzer $compactFuncCallAnalyzer)
    {
    }
    public function matchArrowFunctionExpr(Closure $closure): ?Expr
    {
        if (count($closure->stmts) !== 1) {
            return null;
        }
        $onlyStmt = $closure->stmts[0];
        if (!$onlyStmt instanceof Return_) {
            return null;
        }
        $return = $onlyStmt;
        if (!$return->expr instanceof Expr) {
            return null;
        }
        if ($this->shouldSkipForUsedReferencedValue($closure)) {
            return null;
        }
        if ($this->shouldSkipForUseVariableUsedByCompact($closure)) {
            return null;
        }
        if ($this->shouldSkipMoreSpecificTypeWithVarDoc($return)) {
            return null;
        }
        return $return->expr;
    }
    private function shouldSkipForUseVariableUsedByCompact(Closure $closure): bool
    {
        $variables = array_map(fn(ClosureUse $use): Variable => $use->var, $closure->uses);
        if ($variables === []) {
            return \false;
        }
        return (bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($closure, function (Node $node) use ($variables): bool {
            if (!$node instanceof FuncCall) {
                return \false;
            }
            $found = array_any($variables, fn($variable) => $this->compactFuncCallAnalyzer->isInCompact($node, $variable));
            return $found;
        });
    }
    /**
     * Ensure @var doc usage to be skipped, as arrow functions do not support
     * inline @var annotations for type narrowing (e.g. generic types like Builder<Team>)
     */
    private function shouldSkipMoreSpecificTypeWithVarDoc(Return_ $return): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($return);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return \false;
        }
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        return $varTagValueNode instanceof VarTagValueNode;
    }
    private function shouldSkipForUsedReferencedValue(Closure $closure): bool
    {
        $referencedValues = $this->resolveReferencedUseVariablesFromClosure($closure);
        if ($referencedValues === []) {
            return \false;
        }
        $isFoundInStmt = (bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($closure, fn(Node $node): bool => array_any($referencedValues, fn($referencedValue): bool => $this->nodeComparator->areNodesEqual($node, $referencedValue)));
        if ($isFoundInStmt) {
            return \true;
        }
        return $this->isFoundInInnerUses($closure, $referencedValues);
    }
    /**
     * @param Variable[] $referencedValues
     */
    private function isFoundInInnerUses(Closure $node, array $referencedValues): bool
    {
        return (bool) $this->betterNodeFinder->findFirstInFunctionLikeScoped($node, function (Node $subNode) use ($referencedValues): bool {
            if (!$subNode instanceof Closure) {
                return \false;
            }
            foreach ($referencedValues as $referencedValue) {
                $isFoundInInnerUses = $this->arrayChecker->doesExist($subNode->uses, fn(ClosureUse $closureUse): bool => $closureUse->byRef && $this->nodeComparator->areNodesEqual($closureUse->var, $referencedValue));
                if ($isFoundInInnerUses) {
                    return \true;
                }
            }
            return \false;
        });
    }
    /**
     * @return Variable[]
     */
    private function resolveReferencedUseVariablesFromClosure(Closure $closure): array
    {
        $referencedValues = [];
        /** @var ClosureUse $use */
        foreach ($closure->uses as $use) {
            if ($use->byRef) {
                $referencedValues[] = $use->var;
            }
        }
        return $referencedValues;
    }
}
