<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
/**
 * @api
 */
final class FileNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var Node[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $nodes;
    /**
     * @param Node[] $nodes
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $nodes
    )
    {
        $this->nodes = $nodes;
        $firstNode = $nodes[0] ?? null;
        parent::__construct($firstNode !== null ? $firstNode->getAttributes() : []);
    }
    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_FileNode';
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
