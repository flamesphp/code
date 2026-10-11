<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('inForeachNode')]
final class InForeachNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Foreach_ $originalNode;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Foreach_ $originalNode
    )
    {
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): Foreach_
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_InForeachNode';
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
