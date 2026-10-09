<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpParser\Node;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\BitwiseAnd as AssignBitwiseAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\BitwiseOr as AssignBitwiseOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\BitwiseXor as AssignBitwiseXor;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Concat as AssignConcat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Div as AssignDiv;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Minus as AssignMinus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Mod as AssignMod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Mul as AssignMul;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Plus as AssignPlus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\Pow as AssignPow;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\ShiftLeft as AssignShiftLeft;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\AssignOp\ShiftRight as AssignShiftRight;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BitwiseXor;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Div;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Greater;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Minus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Mod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Mul;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Plus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Pow;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\ShiftLeft;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\ShiftRight;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Smaller;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
final class AssignAndBinaryMap
{
    /**
     * @var array<class-string<BinaryOp>, class-string<BinaryOp>>
     */
    private const array BINARY_OP_TO_INVERSE_CLASSES = [Identical::class => NotIdentical::class, NotIdentical::class => Identical::class, Equal::class => NotEqual::class, NotEqual::class => Equal::class, Greater::class => SmallerOrEqual::class, Smaller::class => GreaterOrEqual::class, GreaterOrEqual::class => Smaller::class, SmallerOrEqual::class => Greater::class];
    /**
     * @var array<class-string<AssignOp>, class-string<BinaryOp>>
     */
    private const array ASSIGN_OP_TO_BINARY_OP_CLASSES = [AssignBitwiseOr::class => BitwiseOr::class, AssignBitwiseAnd::class => BitwiseAnd::class, AssignBitwiseXor::class => BitwiseXor::class, AssignPlus::class => Plus::class, AssignDiv::class => Div::class, AssignMul::class => Mul::class, AssignMinus::class => Minus::class, AssignConcat::class => Concat::class, AssignPow::class => Pow::class, AssignMod::class => Mod::class, AssignShiftLeft::class => ShiftLeft::class, AssignShiftRight::class => ShiftRight::class];
    /**
     * @var array<class-string<BinaryOp>, class-string<AssignOp>>
     */
    private array $binaryOpToAssignClasses;
    public function __construct()
    {
        /** @var array<class-string<BinaryOp>, class-string<AssignOp>> $binaryClassesToAssignOp */
        $binaryClassesToAssignOp = array_flip(self::ASSIGN_OP_TO_BINARY_OP_CLASSES);
        $this->binaryOpToAssignClasses = $binaryClassesToAssignOp;
    }
    /**
     * @return class-string<AssignOp>|null
     */
    public function getAlternative(BinaryOp $binaryOp): ?string
    {
        return $this->binaryOpToAssignClasses[$binaryOp::class] ?? null;
    }
    /**
     * @return class-string<BinaryOp>|null
     */
    public function getInversed(BinaryOp $binaryOp): ?string
    {
        $nodeClass = $binaryOp::class;
        return self::BINARY_OP_TO_INVERSE_CLASSES[$nodeClass] ?? null;
    }
}
