<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeNestingScope\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Match_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Case_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Catch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ElseIf_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
final class ControlStructure
{
    /**
     * These situations happens only if condition is met
     * @var array<class-string<Node>>
     */
    public const array CONDITIONAL_NODE_SCOPE_TYPES = [If_::class, While_::class, Do_::class, Else_::class, ElseIf_::class, Catch_::class, Case_::class, Match_::class, Switch_::class, Foreach_::class];
}
