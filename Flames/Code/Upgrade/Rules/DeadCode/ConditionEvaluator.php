<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotEqual;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\NotIdentical;
use Flames\Code\Upgrade\Rules\DeadCode\Contract\ConditionInterface;
use Flames\Code\Upgrade\Rules\DeadCode\ValueObject\BinaryToVersionCompareCondition;
use Flames\Code\Upgrade\Rules\DeadCode\ValueObject\VersionCompareCondition;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
final readonly class ConditionEvaluator
{
    public function __construct(private PhpVersionProvider $phpVersionProvider)
    {
    }
    /**
     * @return bool|int|null
     */
    public function evaluate(ConditionInterface $condition)
    {
        if ($condition instanceof VersionCompareCondition) {
            return $this->evaluateVersionCompareCondition($condition);
        }
        if ($condition instanceof BinaryToVersionCompareCondition) {
            return $this->isEvaluatedAsTrue($condition);
        }
        return null;
    }
    /**
     * @return bool|int|null
     */
    private function evaluateVersionCompareCondition(VersionCompareCondition $versionCompareCondition)
    {
        $compareSign = $versionCompareCondition->getCompareSign();
        if ($compareSign !== null) {
            if ($compareSign === '<' && $this->phpVersionProvider->provide() < $versionCompareCondition->getSecondVersion()) {
                return null;
            }
            return version_compare((string) $versionCompareCondition->getFirstVersion(), (string) $versionCompareCondition->getSecondVersion(), $compareSign);
        }
        return version_compare((string) $versionCompareCondition->getFirstVersion(), (string) $versionCompareCondition->getSecondVersion());
    }
    private function isEvaluatedAsTrue(BinaryToVersionCompareCondition $binaryToVersionCompareCondition): bool
    {
        $versionCompareResult = $this->evaluateVersionCompareCondition($binaryToVersionCompareCondition->getVersionCompareCondition());
        if ($binaryToVersionCompareCondition->getBinaryClass() === Identical::class) {
            return $binaryToVersionCompareCondition->getExpectedValue() === $versionCompareResult;
        }
        if ($binaryToVersionCompareCondition->getBinaryClass() === NotIdentical::class) {
            return $binaryToVersionCompareCondition->getExpectedValue() !== $versionCompareResult;
        }
        if ($binaryToVersionCompareCondition->getBinaryClass() === Equal::class) {
            // weak comparison on purpose
            return $binaryToVersionCompareCondition->getExpectedValue() === $versionCompareResult;
        }
        if ($binaryToVersionCompareCondition->getBinaryClass() === NotEqual::class) {
            // weak comparison on purpose
            return $binaryToVersionCompareCondition->getExpectedValue() !== $versionCompareResult;
        }
        throw new ShouldNotHappenException();
    }
}
