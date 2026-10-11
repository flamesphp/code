<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\Rules\DeadCode\Contract\ConditionInterface;
use Flames\Code\Upgrade\Rules\DeadCode\ValueObject\BinaryToVersionCompareCondition;
use Flames\Code\Upgrade\Rules\DeadCode\ValueObject\VersionCompareCondition;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Util\PhpVersionFactory;
final readonly class ConditionResolver
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private PhpVersionProvider $phpVersionProvider, private ValueResolver $valueResolver)
    {
    }
    public function resolveFromExpr(Expr $expr): ?ConditionInterface
    {
        if ($this->isVersionCompareFuncCall($expr)) {
            /** @var FuncCall $expr */
            return $this->resolveVersionCompareConditionForFuncCall($expr);
        }
        if (!$expr instanceof Identical && !$expr instanceof Equal && !$expr instanceof NotIdentical && !$expr instanceof NotEqual) {
            return null;
        }
        $binaryClass = $expr::class;
        if ($this->isVersionCompareFuncCall($expr->left)) {
            /** @var FuncCall $funcCall */
            $funcCall = $expr->left;
            return $this->resolveFuncCall($funcCall, $expr->right, $binaryClass);
        }
        if ($this->isVersionCompareFuncCall($expr->right)) {
            /** @var FuncCall $funcCall */
            $funcCall = $expr->right;
            $versionCompareCondition = $this->resolveVersionCompareConditionForFuncCall($funcCall);
            if (!$versionCompareCondition instanceof VersionCompareCondition) {
                return null;
            }
            $expectedValue = $this->valueResolver->getValue($expr->left);
            return new BinaryToVersionCompareCondition($versionCompareCondition, $binaryClass, $expectedValue);
        }
        return null;
    }
    private function isVersionCompareFuncCall(Expr $expr): bool
    {
        if (!$expr instanceof FuncCall) {
            return \false;
        }
        return $this->nodeNameResolver->isName($expr, 'version_compare');
    }
    private function resolveVersionCompareConditionForFuncCall(FuncCall $funcCall): ?VersionCompareCondition
    {
        $firstVersion = $this->resolveArgumentValue($funcCall, 0);
        if ($firstVersion === null) {
            return null;
        }
        $secondVersion = $this->resolveArgumentValue($funcCall, 1);
        if ($secondVersion === null) {
            return null;
        }
        // includes compare sign as 3rd argument
        $versionCompareSign = null;
        if (isset($funcCall->args[2]) && $funcCall->args[2] instanceof Arg) {
            $versionCompareSign = $this->valueResolver->getValue($funcCall->args[2]->value);
        }
        return new VersionCompareCondition($firstVersion, $secondVersion, $versionCompareSign);
    }
    private function resolveFuncCall(FuncCall $funcCall, Expr $expr, string $binaryClass): ?BinaryToVersionCompareCondition
    {
        $versionCompareCondition = $this->resolveVersionCompareConditionForFuncCall($funcCall);
        if (!$versionCompareCondition instanceof VersionCompareCondition) {
            return null;
        }
        $expectedValue = $this->valueResolver->getValue($expr);
        return new BinaryToVersionCompareCondition($versionCompareCondition, $binaryClass, $expectedValue);
    }
    private function resolveArgumentValue(FuncCall $funcCall, int $argumentPosition): ?int
    {
        if (!isset($funcCall->args[$argumentPosition])) {
            return null;
        }
        if (!$funcCall->args[$argumentPosition] instanceof Arg) {
            return null;
        }
        $firstArgValue = $funcCall->args[$argumentPosition]->value;
        /** @var mixed|null $version */
        $version = $this->valueResolver->getValue($firstArgValue);
        if (in_array($version, ['PHP_VERSION', 'PHP_VERSION_ID'], \true)) {
            return $this->phpVersionProvider->provide();
        }
        // version_compare() juggles the version to string first, e.g. 7.3 to "7.3"
        if (is_float($version)) {
            $version = (string) $version;
        }
        if (is_string($version)) {
            return PhpVersionFactory::createIntVersion($version);
        }
        if (!is_int($version)) {
            return null;
        }
        return $version;
    }
}
