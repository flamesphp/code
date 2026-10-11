<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function in_array;
use function strtolower;
use function substr;
#[AutowiredService]
final class OpenSslEncryptParameterOutTypeExtension implements FunctionParameterOutTypeExtension
{
    private \PHPStan\Type\Php\OpenSslCipherMethodsProvider $cipherMethodsProvider;
    public function __construct(\PHPStan\Type\Php\OpenSslCipherMethodsProvider $cipherMethodsProvider)
    {
        $this->cipherMethodsProvider = $cipherMethodsProvider;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
    {
        return $functionReflection->getName() === 'openssl_encrypt' && $parameter->getName() === 'tag';
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function getParameterOutTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, ParameterReflection $parameter, \PHPStan\Analyser\Scope $scope): ?Type
    {
        $args = $funcCall->getArgs();
        $cipherArg = $args[1] ?? null;
        if ($cipherArg === null) {
            return null;
        }
        $tagTypes = [];
        foreach ($scope->getType($cipherArg->value)->getConstantStrings() as $cipherType) {
            $cipher = strtolower($cipherType->getValue());
            $mode = substr($cipher, -3);
            if (!$this->cipherMethodsProvider->isSupportedCipherMethod($cipher, $scope)) {
                $tagTypes[] = new NullType();
                continue;
            }
            if (in_array($mode, ['gcm', 'ccm'], \true)) {
                $tagTypes[] = new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
                continue;
            }
            $tagTypes[] = new NullType();
        }
        if ($tagTypes === []) {
            return new UnionType([new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]), new NullType()]);
        }
        return TypeCombinator::union(...$tagTypes);
    }
}
