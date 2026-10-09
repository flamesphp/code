<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\MatchAndRefactor\StrStartsWithMatchAndRefactor;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\Php80\Contract\StrStartWithMatchAndRefactorInterface;
use Flames\Code\Upgrade\Rules\Php80\NodeFactory\StrStartsWithFuncCallFactory;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\StrStartsWith;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
final readonly class StrposMatchAndRefactor implements StrStartWithMatchAndRefactorInterface
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private ValueResolver $valueResolver, private StrStartsWithFuncCallFactory $strStartsWithFuncCallFactory)
    {
    }
    /**
     * @param \PhpParser\Node\Expr\BinaryOp\Identical|\PhpParser\Node\Expr\BinaryOp\NotIdentical|\PhpParser\Node\Expr\BinaryOp\Equal|\PhpParser\Node\Expr\BinaryOp\NotEqual $binaryOp
     */
    public function match($binaryOp): ?StrStartsWith
    {
        $isPositive = $binaryOp instanceof Identical || $binaryOp instanceof Equal;
        if ($binaryOp->left instanceof FuncCall && $this->nodeNameResolver->isName($binaryOp->left, 'strpos')) {
            return $this->processBinaryOpLeft($binaryOp, $isPositive);
        }
        if (!$binaryOp->right instanceof FuncCall) {
            return null;
        }
        if (!$this->nodeNameResolver->isName($binaryOp->right, 'strpos')) {
            return null;
        }
        return $this->processBinaryOpRight($binaryOp, $isPositive);
    }
    /**
     * @return FuncCall|BooleanNot
     */
    public function refactorStrStartsWith(StrStartsWith $strStartsWith): Node
    {
        $strposFuncCall = $strStartsWith->getFuncCall();
        $strposFuncCall->name = new Name('str_starts_with');
        return $this->strStartsWithFuncCallFactory->createStrStartsWith($strStartsWith);
    }
    private function processBinaryOpLeft(BinaryOp $binaryOp, bool $isPositive): ?StrStartsWith
    {
        if (!$this->valueResolver->isValue($binaryOp->right, 0)) {
            return null;
        }
        /** @var FuncCall $funcCall */
        $funcCall = $binaryOp->left;
        if ($funcCall->isFirstClassCallable()) {
            return null;
        }
        if (count($funcCall->getArgs()) < 2) {
            return null;
        }
        $haystack = $funcCall->getArgs()[0]->value;
        $needle = $funcCall->getArgs()[1]->value;
        return new StrStartsWith($funcCall, $haystack, $needle, $isPositive);
    }
    private function processBinaryOpRight(BinaryOp $binaryOp, bool $isPositive): ?StrStartsWith
    {
        if (!$this->valueResolver->isValue($binaryOp->left, 0)) {
            return null;
        }
        /** @var FuncCall $funcCall */
        $funcCall = $binaryOp->right;
        if (count($funcCall->getArgs()) < 2) {
            return null;
        }
        $haystack = $funcCall->getArgs()[0]->value;
        $needle = $funcCall->getArgs()[1]->value;
        return new StrStartsWith($funcCall, $haystack, $needle, $isPositive);
    }
}
