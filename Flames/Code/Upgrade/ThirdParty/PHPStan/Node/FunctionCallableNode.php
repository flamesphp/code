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
#[ReferencedByTurboExtension('functionCallableNode')]
final class FunctionCallableNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Name|\PhpParser\Node\Expr
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Expr\FuncCall $originalNode;
    /**
     * @param \PhpParser\Node\Name|\PhpParser\Node\Expr $name
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Expr\FuncCall $originalNode
    )
    {
        $this->name = $name;
        $this->originalNode = $originalNode;
        // the original's printed expression key must not become this node's
        $attributes = $this->originalNode->getAttributes();
        unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
        parent::__construct($attributes);
    }
    /**
     * @return Expr|Name
     */
    public function getName()
    {
        return $this->name;
    }
    public function getOriginalNode(): Expr\FuncCall
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_FunctionCallableNode';
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
