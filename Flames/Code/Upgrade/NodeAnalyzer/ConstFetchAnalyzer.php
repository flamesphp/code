<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
/**
 * Read-only utils for ClassConstAnalyzer Node:
 * "false, true..."
 */
final class ConstFetchAnalyzer
{
    public function isTrueOrFalse(Expr $expr): bool
    {
        if ($this->isTrue($expr)) {
            return \true;
        }
        return $this->isFalse($expr);
    }
    public function isFalse(Expr $expr): bool
    {
        return $this->isConstantWithLowercasedName($expr, 'false');
    }
    public function isTrue(Expr $expr): bool
    {
        return $this->isConstantWithLowercasedName($expr, 'true');
    }
    public function isNull(Expr $expr): bool
    {
        return $this->isConstantWithLowercasedName($expr, 'null');
    }
    private function isConstantWithLowercasedName(Node $node, string $name): bool
    {
        if (!$node instanceof ConstFetch) {
            return \false;
        }
        return $node->name->toLowerString() === $name;
    }
}
