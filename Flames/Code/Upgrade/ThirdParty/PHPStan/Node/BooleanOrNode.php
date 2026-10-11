<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('booleanOrNode')]
final class BooleanOrNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr\BinaryOp\BooleanOr|\PhpParser\Node\Expr\BinaryOp\LogicalOr
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $rightScope;
    /**
     * @param \PhpParser\Node\Expr\BinaryOp\BooleanOr|\PhpParser\Node\Expr\BinaryOp\LogicalOr $originalNode
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Scope $rightScope
    )
    {
        $this->originalNode = $originalNode;
        $this->rightScope = $rightScope;
        // the original's printed expression key must not become this node's
        $attributes = $originalNode->getAttributes();
        unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
        parent::__construct($attributes);
    }
    /**
     * @return \PhpParser\Node\Expr\BinaryOp\BooleanOr|\PhpParser\Node\Expr\BinaryOp\LogicalOr
     */
    public function getOriginalNode()
    {
        return $this->originalNode;
    }
    public function getRightScope(): Scope
    {
        return $this->rightScope;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_BooleanOrNode';
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
