<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArgPlaceholder;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class CompactFuncCallAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function isInCompact(FuncCall $funcCall, Variable $variable): bool
    {
        if (!$this->nodeNameResolver->isName($funcCall, 'compact')) {
            return \false;
        }
        if (!is_string($variable->name)) {
            return \false;
        }
        return $this->isInArgOrArrayItemNodes($funcCall->args, $variable->name);
    }
    /**
     * @param array<int, Arg|ArgPlaceholder|VariadicPlaceholder|ArrayItem|null> $nodes
     */
    private function isInArgOrArrayItemNodes(array $nodes, string $variableName): bool
    {
        foreach ($nodes as $node) {
            if ($this->shouldSkip($node)) {
                continue;
            }
            /** @var Arg|ArrayItem $node */
            if ($node->value instanceof Array_) {
                if ($this->isInArgOrArrayItemNodes($node->value->items, $variableName)) {
                    return \true;
                }
                continue;
            }
            if (!$node->value instanceof String_) {
                continue;
            }
            if ($node->value->value === $variableName) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArgPlaceholder|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ArrayItem|null $node
     */
    private function shouldSkip($node): bool
    {
        if ($node === null) {
            return \true;
        }
        return !$node instanceof Arg && !$node instanceof ArrayItem;
    }
}
