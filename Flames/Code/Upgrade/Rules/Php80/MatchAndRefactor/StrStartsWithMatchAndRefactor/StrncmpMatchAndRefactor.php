<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\MatchAndRefactor\StrStartsWithMatchAndRefactor;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Rules\Php80\Contract\StrStartWithMatchAndRefactorInterface;
use Flames\Code\Upgrade\Rules\Php80\NodeFactory\StrStartsWithFuncCallFactory;
use Flames\Code\Upgrade\Rules\Php80\ValueObject\StrStartsWith;
use Flames\Code\Upgrade\Rules\Php80\ValueObjectFactory\StrStartsWithFactory;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
final readonly class StrncmpMatchAndRefactor implements StrStartWithMatchAndRefactorInterface
{
    private const string FUNCTION_NAME = 'strncmp';
    public function __construct(private NodeNameResolver $nodeNameResolver, private StrStartsWithFactory $strStartsWithFactory, private NodeComparator $nodeComparator, private StrStartsWithFuncCallFactory $strStartsWithFuncCallFactory)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual $binaryOp
     */
    public function match($binaryOp): ?StrStartsWith
    {
        $isPositive = $binaryOp instanceof Identical || $binaryOp instanceof Equal;
        if ($binaryOp->left instanceof FuncCall && $this->nodeNameResolver->isName($binaryOp->left, self::FUNCTION_NAME)) {
            return $this->strStartsWithFactory->createFromFuncCall($binaryOp->left, $isPositive);
        }
        if (!$binaryOp->right instanceof FuncCall) {
            return null;
        }
        if (!$this->nodeNameResolver->isName($binaryOp->right, self::FUNCTION_NAME)) {
            return null;
        }
        return $this->strStartsWithFactory->createFromFuncCall($binaryOp->right, $isPositive);
    }
    public function refactorStrStartsWith(StrStartsWith $strStartsWith): ?Node
    {
        if ($this->isNeedleExprWithStrlen($strStartsWith)) {
            return $this->strStartsWithFuncCallFactory->createStrStartsWith($strStartsWith);
        }
        if ($this->isHardcodedStringWithLNumberLength($strStartsWith)) {
            return $this->strStartsWithFuncCallFactory->createStrStartsWith($strStartsWith);
        }
        return null;
    }
    private function isNeedleExprWithStrlen(StrStartsWith $strStartsWith): bool
    {
        $strncmpFuncCall = $strStartsWith->getFuncCall();
        $needleExpr = $strStartsWith->getNeedleExpr();
        if ($strncmpFuncCall->isFirstClassCallable()) {
            return \false;
        }
        if (count($strncmpFuncCall->getArgs()) < 2) {
            return \false;
        }
        $thirdArg = $strncmpFuncCall->getArgs()[2];
        $thirdArgExpr = $thirdArg->value;
        if (!$thirdArgExpr instanceof FuncCall) {
            return \false;
        }
        if (!$this->nodeNameResolver->isName($thirdArgExpr, 'strlen')) {
            return \false;
        }
        $strlenFuncCall = $thirdArgExpr;
        $strlenExpr = $strlenFuncCall->getArgs()[0]->value;
        return $this->nodeComparator->areNodesEqual($needleExpr, $strlenExpr);
    }
    private function isHardcodedStringWithLNumberLength(StrStartsWith $strStartsWith): bool
    {
        $strncmpFuncCall = $strStartsWith->getFuncCall();
        if (count($strncmpFuncCall->getArgs()) < 2) {
            return \false;
        }
        $hardcodedStringNeedle = $strncmpFuncCall->getArgs()[1]->value;
        if (!$hardcodedStringNeedle instanceof String_) {
            return \false;
        }
        $lNumberLength = $strncmpFuncCall->getArgs()[2]->value;
        if (!$lNumberLength instanceof Int_) {
            return \false;
        }
        return $lNumberLength->value === strlen($hardcodedStringNeedle->value);
    }
}
