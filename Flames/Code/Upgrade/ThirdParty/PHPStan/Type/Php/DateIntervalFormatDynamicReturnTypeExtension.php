<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
#[AutowiredService]
final class DateIntervalFormatDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private \PHPStan\Type\Php\DateIntervalFormatReturnTypeHelper $helper;
    public function __construct(\PHPStan\Type\Php\DateIntervalFormatReturnTypeHelper $helper)
    {
        $this->helper = $helper;
    }
    public function getClass(): string
    {
        return DateInterval::class;
    }
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'format';
    }
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $arguments = $methodCall->getArgs();
        if (!isset($arguments[0])) {
            return null;
        }
        return $this->helper->getType($scope->getType($arguments[0]->value), $scope->getType($methodCall->var), $scope);
    }
}
