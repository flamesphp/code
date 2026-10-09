<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * Handles the remaining compound param type group: everything that is neither a pure object,
 * a pure scalar, nor a pure array (e.g. cross-group unions like array|string, iterable, callable).
 *
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ObjectParamTypeByMethodCallTypeUpgrade for object types
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ScalarParamTypeByMethodCallTypeUpgrade for scalar types
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ArrayParamTypeByMethodCallTypeUpgrade for array types
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRectorTest
 */
final class ParamTypeByMethodCallTypeRector extends \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AbstractParamTypeByMethodCallTypeRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change compound param type based on passed method call type', [new CodeSample(<<<'CODE_SAMPLE'
class SomeTypedService
{
    public function run(iterable $values)
    {
    }
}

final class UseDependency
{
    public function __construct(
        private SomeTypedService $someTypedService
    ) {
    }

    public function go($value)
    {
        $this->someTypedService->run($value);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeTypedService
{
    public function run(iterable $values)
    {
    }
}

final class UseDependency
{
    public function __construct(
        private SomeTypedService $someTypedService
    ) {
    }

    public function go(iterable $value)
    {
        $this->someTypedService->run($value);
    }
}
CODE_SAMPLE
)]);
    }
    protected function isMatchingParamType(Type $type): bool
    {
        $bareType = TypeCombinator::removeNull($type);
        // remaining compound types: not a pure object, scalar, nor array (iterable, callable, cross-group unions)
        return !$bareType->isObject()->yes() && !$bareType->isScalar()->yes() && !$bareType->isArray()->yes();
    }
}
