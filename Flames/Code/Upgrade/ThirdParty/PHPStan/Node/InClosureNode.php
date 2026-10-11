<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureType;
/**
 * @api
 */
#[ReferencedByTurboExtension('inClosureNode')]
final class InClosureNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClosureType $closureType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Expr\Closure $originalNode;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClosureType $closureType,
        Closure $originalNode
    )
    {
        $this->closureType = $closureType;
        parent::__construct($originalNode->getAttributes());
        $this->originalNode = $originalNode;
    }
    public function getClosureType(): ClosureType
    {
        return $this->closureType;
    }
    public function getOriginalNode(): Closure
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_InClosureNode';
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
