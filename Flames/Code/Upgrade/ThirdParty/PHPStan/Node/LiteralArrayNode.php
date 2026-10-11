<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\Array_;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('literalArrayNode')]
final class LiteralArrayNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var LiteralArrayItem[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $itemNodes;
    /**
     * @param LiteralArrayItem[] $itemNodes
     */
    public function __construct(
        Array_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $itemNodes
    )
    {
        $this->itemNodes = $itemNodes;
        parent::__construct($originalNode->getAttributes());
    }
    /**
     * @return LiteralArrayItem[]
     */
    public function getItemNodes(): array
    {
        return $this->itemNodes;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_LiteralArray';
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
