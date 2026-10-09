<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Include_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\NodeAnalyzer\CompactFuncCallAnalyzer;
final readonly class ExprUsedInNodeAnalyzer
{
    public function __construct(private \Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\UsedVariableNameAnalyzer $usedVariableNameAnalyzer, private CompactFuncCallAnalyzer $compactFuncCallAnalyzer)
    {
    }
    public function isUsed(Node $node, Variable $variable): bool
    {
        if ($node instanceof Include_) {
            return \true;
        }
        // variable as variable variable need mark as used
        if ($node instanceof Variable && $node->name instanceof Expr) {
            return \true;
        }
        if ($node instanceof FuncCall) {
            return $this->compactFuncCallAnalyzer->isInCompact($node, $variable);
        }
        return $this->usedVariableNameAnalyzer->isVariableNamed($node, $variable);
    }
}
