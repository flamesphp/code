<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('booleanAndNode')]
final class BooleanAndNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr\BinaryOp\BooleanAnd|\PhpParser\Node\Expr\BinaryOp\LogicalAnd
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Scope $rightScope;
    /**
     * @param \PhpParser\Node\Expr\BinaryOp\BooleanAnd|\PhpParser\Node\Expr\BinaryOp\LogicalAnd $originalNode
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
     * @return \PhpParser\Node\Expr\BinaryOp\BooleanAnd|\PhpParser\Node\Expr\BinaryOp\LogicalAnd
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
        return 'PHPStan_Node_BooleanAndNode';
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
