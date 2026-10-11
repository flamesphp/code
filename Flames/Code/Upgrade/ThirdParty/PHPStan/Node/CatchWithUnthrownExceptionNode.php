<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @api
 */
#[ReferencedByTurboExtension('catchWithUnthrownExceptionNode')]
final class CatchWithUnthrownExceptionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Catch_ $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $caughtType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Type $originalCaughtType;
    public function __construct(
        Catch_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $caughtType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Type $originalCaughtType
    )
    {
        $this->originalNode = $originalNode;
        $this->caughtType = $caughtType;
        $this->originalCaughtType = $originalCaughtType;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): Catch_
    {
        return $this->originalNode;
    }
    public function getCaughtType(): Type
    {
        return $this->caughtType;
    }
    public function getOriginalCaughtType(): Type
    {
        return $this->originalCaughtType;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_CatchWithUnthrownExceptionNode';
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
