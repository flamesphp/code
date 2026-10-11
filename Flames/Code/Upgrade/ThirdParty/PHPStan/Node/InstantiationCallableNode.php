<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('instantiationCallableNode')]
final class InstantiationCallableNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Name|\PhpParser\Node\Expr
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $class;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr\New_ $originalNode;
    /**
     * @param \PhpParser\Node\Name|\PhpParser\Node\Expr $class
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $class,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr\New_ $originalNode
    )
    {
        $this->class = $class;
        $this->originalNode = $originalNode;
        // the original's printed expression key must not become this node's
        $attributes = $this->originalNode->getAttributes();
        unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
        parent::__construct($attributes);
    }
    /**
     * @return Expr|Name
     */
    public function getClass()
    {
        return $this->class;
    }
    public function getOriginalNode(): Expr\New_
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_InstantiationCallableNode';
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
