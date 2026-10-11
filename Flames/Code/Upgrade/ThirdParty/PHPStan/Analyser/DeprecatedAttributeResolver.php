<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function count;
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/DeprecatedAttributeResolver.cpp')]
final class DeprecatedAttributeResolver
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
    }
    /**
     * @return array{bool, string|null}
     * @param \PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\PropertyHook $stmt
     */
    public function getDeprecatedAttribute(\PHPStan\Analyser\Scope $scope, $stmt): array
    {
        $initializerExprContext = InitializerExprContext::fromStubParameter($scope->isInClass() ? $scope->getClassReflection()->getName() : null, $scope->getFile(), $stmt);
        $isDeprecated = \false;
        $deprecatedDescription = null;
        $deprecatedDescriptionType = null;
        foreach ($stmt->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->name !== 'Deprecated') {
                    continue;
                }
                $isDeprecated = \true;
                $arguments = $attr->args;
                foreach ($arguments as $i => $arg) {
                    $argName = $arg->name;
                    if ($argName === null) {
                        if ($i !== 0) {
                            continue;
                        }
                        $deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext);
                        break;
                    }
                    if ($argName->name !== 'message') {
                        continue;
                    }
                    $deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext);
                    break;
                }
            }
        }
        if ($deprecatedDescriptionType !== null) {
            $constantStrings = $deprecatedDescriptionType->getConstantStrings();
            if (count($constantStrings) === 1) {
                $deprecatedDescription = $constantStrings[0]->getValue();
            }
        }
        return [$isDeprecated, $deprecatedDescription];
    }
}
