<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Renaming\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
final class SwitchManipulator
{
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function removeBreakNodes(array $stmts): array
    {
        foreach ($stmts as $key => $node) {
            if (!$node instanceof Break_) {
                continue;
            }
            if (!$node->num instanceof Int_ || $node->num->value === 1) {
                unset($stmts[$key]);
                continue;
            }
            $node->num = $node->num->value === 2 ? null : new Int_($node->num->value - 1);
        }
        return $stmts;
    }
}
