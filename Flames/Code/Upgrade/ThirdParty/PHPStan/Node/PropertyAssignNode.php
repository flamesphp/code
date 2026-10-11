<?php

declare (strict_types=1);
namespace PHPStan\Node;

use ArrayAccess;
use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\SetExistingOffsetValueTypeExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\UnsetOffsetExpr;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ObjectType;
#[ReferencedByTurboExtension('propertyAssignNode')]
final class PropertyAssignNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $propertyFetch;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $assignedExpr;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $assignOp;
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $propertyFetch,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $assignedExpr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $assignOp
    )
    {
        $this->propertyFetch = $propertyFetch;
        $this->assignedExpr = $assignedExpr;
        $this->assignOp = $assignOp;
        parent::__construct($propertyFetch->getAttributes());
    }
    /**
     * @return \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    public function getPropertyFetch()
    {
        return $this->propertyFetch;
    }
    public function getAssignedExpr(): Expr
    {
        return $this->assignedExpr;
    }
    public function isAssignOp(): bool
    {
        return $this->assignOp;
    }
    /**
     * Whether the assignment is an offset write ($this->prop[...] = ...,
     * unset($this->prop[...])) on an ArrayAccess object, which goes through
     * offsetSet()/offsetUnset() rather than reassigning the property itself.
     */
    public function isArrayAccessOffsetWrite(Scope $scope): bool
    {
        if (!$this->assignedExpr instanceof SetOffsetValueTypeExpr && !$this->assignedExpr instanceof SetExistingOffsetValueTypeExpr && !$this->assignedExpr instanceof UnsetOffsetExpr) {
            return \false;
        }
        return (new ObjectType(ArrayAccess::class))->isSuperTypeOf($scope->getType($this->assignedExpr->getVar()))->yes();
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_PropertyAssignNodeNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
}
