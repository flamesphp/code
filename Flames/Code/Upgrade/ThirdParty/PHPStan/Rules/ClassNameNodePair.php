<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node;
final class ClassNameNodePair
{
    /**
     * @var non-empty-string
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $className;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node $node;
    /**
     * @param non-empty-string $className
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $className,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node $node
    )
    {
        $this->className = $className;
        $this->node = $node;
    }
    /**
     * @return non-empty-string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
    public function getNode(): Node
    {
        return $this->node;
    }
}
