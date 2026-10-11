<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\Enum;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Block;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Case_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Catch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Declare_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ElseIf_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Finally_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\For_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\TryCatch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
final class NodeGroup
{
    /**
     * These nodes have Stmt[] $stmts iterable public property
     *
     * If https://github.com/nikic/PHP-Parser/pull/1113 gets merged, can replace those.
     *
     * @var array<class-string<Node>>
     */
    public const array STMTS_AWARE = [Block::class, Closure::class, Case_::class, Catch_::class, ClassMethod::class, Do_::class, Else_::class, ElseIf_::class, Finally_::class, For_::class, Foreach_::class, Function_::class, If_::class, Namespace_::class, TryCatch::class, While_::class, FileNode::class, Declare_::class];
    /**
     * @var array<class-string<Node>>
     */
    public const array STMTS_TO_HAVE_NEXT_NEWLINE = [ClassMethod::class, Function_::class, Property::class, If_::class, Foreach_::class, Do_::class, While_::class, For_::class, ClassConst::class, TryCatch::class, Class_::class, Trait_::class, Interface_::class, Switch_::class];
    public static function isStmtAwareNode(Node $node): bool
    {
        $found = array_any(self::STMTS_AWARE, fn($stmtAwareClass) => $node instanceof $stmtAwareClass);
        return $found;
    }
}
