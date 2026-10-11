<?php

declare (strict_types=1);
namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('propertyWrite')]
final class PropertyWrite
{
    /**
     * @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    private $fetch;
    private Scope $scope;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $promotedPropertyWrite;
    /**
     * @var \PHPStan\Node\ClassPropertyNode|\PHPStan\Node\PropertyAssignNode|\PhpParser\Node\Expr\AssignRef|null
     */
    private $originalNode;
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $fetch
     * @param \PHPStan\Node\ClassPropertyNode|\PHPStan\Node\PropertyAssignNode|\PhpParser\Node\Expr\AssignRef|null $originalNode
     */
    public function __construct(
        $fetch,
        Scope $scope,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $promotedPropertyWrite,
        $originalNode = null
    )
    {
        $this->fetch = $fetch;
        $this->scope = $scope;
        $this->promotedPropertyWrite = $promotedPropertyWrite;
        $this->originalNode = $originalNode;
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
    public function isPromotedPropertyWrite(): bool
    {
        return $this->promotedPropertyWrite;
    }
    /**
     * Whether the write happens through offset access ($this->prop[...] = ...)
     * on an ArrayAccess object, which goes through offsetSet() rather than
     * reassigning the property itself.
     */
    public function isViaOffsetAccess(): bool
    {
        if (!$this->originalNode instanceof PropertyAssignNode) {
            return \false;
        }
        return $this->originalNode->isArrayAccessOffsetWrite($this->scope);
    }
}
