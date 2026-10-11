<?php

declare (strict_types=1);
namespace PHPStan\Node\Property;

use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('propertyAssign')]
final class PropertyAssign
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PropertyAssignNode $assign;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PropertyAssignNode $assign,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope
    )
    {
        $this->assign = $assign;
        $this->scope = $scope;
    }
    public function getAssign(): PropertyAssignNode
    {
        return $this->assign;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
}
