<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('staticMethodCallableNode')]
final class StaticMethodCallableNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Name|\PhpParser\Node\Expr
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $class;
    /**
     * @var \PhpParser\Node\Identifier|\PhpParser\Node\Expr
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr\StaticCall $originalNode;
    /**
     * @param \PhpParser\Node\Name|\PhpParser\Node\Expr $class
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Expr $name
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $class,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr\StaticCall $originalNode
    )
    {
        $this->class = $class;
        $this->name = $name;
        $this->originalNode = $originalNode;
        // the original's printed expression key must not become this node's
        $attributes = $originalNode->getAttributes();
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
    /**
     * @return Identifier|Expr
     */
    public function getName()
    {
        return $this->name;
    }
    public function getOriginalNode(): Expr\StaticCall
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_StaticMethodCallableNode';
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
