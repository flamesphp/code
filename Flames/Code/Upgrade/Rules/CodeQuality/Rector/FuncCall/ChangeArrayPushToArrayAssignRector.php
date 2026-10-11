<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRectorTest
 */
final class ChangeArrayPushToArrayAssignRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change array_push() to direct variable assign', [new CodeSample(<<<'CODE_SAMPLE'
$items = [];
array_push($items, $item);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$items = [];
$items[] = $item;
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Expression::class];
    }
    /**
     * @param Expression $node
     * @return Stmt[]|null
     */
    public function refactor(Node $node): ?array
    {
        if (!$node->expr instanceof FuncCall) {
            return null;
        }
        $funcCall = $node->expr;
        if (!$this->isName($funcCall, 'array_push')) {
            return null;
        }
        if ($funcCall->isFirstClassCallable()) {
            return null;
        }
        if ($this->hasArraySpread($funcCall)) {
            return null;
        }
        $args = $funcCall->getArgs();
        if ($args === []) {
            return null;
        }
        $firstArg = array_shift($args);
        if ($args === []) {
            return null;
        }
        $arrayDimFetch = new ArrayDimFetch($firstArg->value);
        $newStmts = [];
        foreach ($args as $key => $arg) {
            $assign = new Assign($arrayDimFetch, $arg->value);
            $assignExpression = new Expression($assign);
            $newStmts[] = $assignExpression;
            // keep comments of first line
            if ($key === 0) {
                $this->mirrorComments($assignExpression, $node);
            }
        }
        return $newStmts;
    }
    private function hasArraySpread(FuncCall $funcCall): bool
    {
        $found = array_any($funcCall->getArgs(), fn($arg) => $arg->unpack);
        return $found;
    }
}
