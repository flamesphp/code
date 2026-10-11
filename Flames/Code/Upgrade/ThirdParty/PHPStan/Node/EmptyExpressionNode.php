<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\Empty_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * Emitted by EmptyHandler once the empty() subject is processed, so EmptyRule
 * reads the already-computed IssetabilityResolution (via the carried
 * ExpressionResult) instead of re-walking the chain on demand.
 *
 * @internal
 */
#[ReferencedByTurboExtension('emptyExpressionNode')]
final class EmptyExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ExpressionResult $exprResult;
    public function __construct(
        Empty_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ExpressionResult $exprResult
    )
    {
        $this->exprResult = $exprResult;
        parent::__construct($originalNode->getAttributes());
    }
    public function getExprResult(): ExpressionResult
    {
        return $this->exprResult;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_EmptyExpressionNode';
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
