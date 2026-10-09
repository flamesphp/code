<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
final class ClassAnalyzer
{
    public function isAnonymousClass(Node $node): bool
    {
        if ($node instanceof New_) {
            return $this->isAnonymousClass($node->class);
        }
        if ($node instanceof Class_) {
            return $node->isAnonymous();
        }
        return \false;
    }
}
