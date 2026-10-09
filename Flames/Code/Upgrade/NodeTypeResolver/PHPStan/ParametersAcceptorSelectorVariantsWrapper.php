<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\PHPStan;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
final class ParametersAcceptorSelectorVariantsWrapper
{
    /**
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection $reflection
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike $node
     */
    public static function select($reflection, $node, Scope $scope): ParametersAcceptor
    {
        $variants = $reflection->getVariants();
        if ($node instanceof FunctionLike) {
            return ParametersAcceptorSelector::combineAcceptors($variants);
        }
        if ($node->isFirstClassCallable()) {
            return ParametersAcceptorSelector::combineAcceptors($variants);
        }
        return ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $variants);
    }
}
