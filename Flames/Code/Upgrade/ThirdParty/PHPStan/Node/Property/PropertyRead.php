<?php

declare (strict_types=1);
namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('propertyRead')]
final class PropertyRead
{
    /**
     * @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $fetch;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $scope;
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $fetch
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $fetch,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $scope
    )
    {
        $this->fetch = $fetch;
        $this->scope = $scope;
    }
    /**
     * @return PropertyFetch|StaticPropertyFetch
     */
    public function getFetch()
    {
        return $this->fetch;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
}
