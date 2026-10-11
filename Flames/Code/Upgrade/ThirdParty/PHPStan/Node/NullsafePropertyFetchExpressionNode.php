<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * Emitted by NullsafePropertyFetchHandler once the receiver has been processed, so
 * NullsafePropertyFetchRule reads the receiver's (possibly null) type from the
 * carried type instead of asking the scope for the receiver type before it is
 * processed.
 *
 * @internal
 */
#[ReferencedByTurboExtension('nullsafePropertyFetchExpressionNode')]
final class NullsafePropertyFetchExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public NullsafePropertyFetch $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $calledOnType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $calledOnNativeType;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        NullsafePropertyFetch $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $calledOnType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $calledOnNativeType
    )
    {
        $this->originalNode = $originalNode;
        $this->calledOnType = $calledOnType;
        $this->calledOnNativeType = $calledOnNativeType;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): NullsafePropertyFetch
    {
        return $this->originalNode;
    }
    public function getCalledOnType(): Type
    {
        return $this->calledOnType;
    }
    public function getCalledOnNativeType(): Type
    {
        return $this->calledOnNativeType;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_NullsafePropertyFetchExpressionNode';
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
