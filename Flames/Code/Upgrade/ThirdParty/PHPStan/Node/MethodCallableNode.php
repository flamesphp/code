<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('methodCallableNode')]
final class MethodCallableNode extends Expr implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr $var;
    /**
     * @var \PhpParser\Node\Identifier|\PhpParser\Node\Expr
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr\MethodCall $originalNode;
    /**
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Expr $name
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr $var,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr\MethodCall $originalNode
    )
    {
        $this->var = $var;
        $this->name = $name;
        $this->originalNode = $originalNode;
        // the original's printed expression key must not become this node's
        $attributes = $originalNode->getAttributes();
        unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
        parent::__construct($attributes);
    }
    public function getVar(): Expr
    {
        return $this->var;
    }
    /**
     * @return Expr|Identifier
     */
    public function getName()
    {
        return $this->name;
    }
    public function getOriginalNode(): Expr\MethodCall
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_MethodCallableNode';
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
