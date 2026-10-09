<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * Handles the object param type group.
 *
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ObjectParamTypeByMethodCallTypeRectorTest
 */
final class ObjectParamTypeByMethodCallTypeRector extends \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AbstractParamTypeByMethodCallTypeRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change object param type based on passed method call type', [new CodeSample(<<<'CODE_SAMPLE'
class SomeTypedService
{
    public function run(SomeObject $object)
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
    public function run(SomeObject $object)
    {
    }
}

final class UseDependency
{
    public function __construct(
        private SomeTypedService $someTypedService
    ) {
    }

    public function go(SomeObject $value)
    {
        $this->someTypedService->run($value);
    }
}
CODE_SAMPLE
)]);
    }
    protected function isMatchingParamType(Type $type): bool
    {
        return TypeCombinator::removeNull($type)->isObject()->yes();
    }
}
