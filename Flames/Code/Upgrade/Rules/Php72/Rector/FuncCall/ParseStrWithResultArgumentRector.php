<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php72\Rector\FuncCall\ParseStrWithResultArgumentRectorTest
 */
final class ParseStrWithResultArgumentRector extends AbstractRector implements MinPhpVersionInterface
{
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::RESULT_ARG_IN_PARSE_STR;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Use $result argument in parse_str() function', [new CodeSample(<<<'CODE_SAMPLE'
parse_str($this->query);
$data = get_defined_vars();
CODE_SAMPLE
, <<<'CODE_SAMPLE'
parse_str($this->query, $result);
$data = $result;
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
     * @return StmtsAware
     */
    public function refactor(Node $node): ?Node
    {
        return $this->processStrWithResult($node, \false);
    }
    /**
     * @param StmtsAware $stmtsAware
     * @return StmtsAware|null
     */
    private function processStrWithResult(Node $stmtsAware, bool $hasChanged, int $jumpToKey = 0): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node
    {
        if ($stmtsAware->stmts === null) {
            return null;
        }
        $totalKeys = array_key_last($stmtsAware->stmts);
        for ($key = $jumpToKey; $key < $totalKeys; ++$key) {
            if (!isset($stmtsAware->stmts[$key], $stmtsAware->stmts[$key + 1])) {
                break;
            }
            $stmt = $stmtsAware->stmts[$key];
            if ($this->shouldSkip($stmt)) {
                continue;
            }
            /** @var Expression $stmt */
            /** @var FuncCall $expr */
            $expr = $stmt->expr;
            $resultVariable = new Variable('result');
            $expr->args[1] = new Arg($resultVariable);
            $nextExpression = $stmtsAware->stmts[$key + 1];
            $this->traverseNodesWithCallable($nextExpression, function (Node $node) use ($resultVariable, &$hasChanged): ?Variable {
                if (!$node instanceof FuncCall) {
                    return null;
                }
                if (!$this->isName($node, 'get_defined_vars')) {
                    return null;
                }
                $hasChanged = \true;
                return $resultVariable;
            });
            return $this->processStrWithResult($stmtsAware, $hasChanged, $key + 2);
        }
        if ($hasChanged) {
            return $stmtsAware;
        }
        return null;
    }
    private function shouldSkip(Stmt $stmt): bool
    {
        if (!$stmt instanceof Expression) {
            return \true;
        }
        if (!$stmt->expr instanceof FuncCall) {
            return \true;
        }
        if (!$this->isName($stmt->expr, 'parse_str')) {
            return \true;
        }
        return isset($stmt->expr->args[1]);
    }
}
