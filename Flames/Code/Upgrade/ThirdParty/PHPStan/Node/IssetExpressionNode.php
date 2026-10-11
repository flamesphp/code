<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\Isset_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * Emitted by IssetHandler once each isset() subject is processed, so IssetRule
 * reads the already-computed IssetabilityResolution (via the carried
 * ExpressionResults) instead of re-walking the chain on demand.
 *
 * @internal
 */
#[ReferencedByTurboExtension('issetExpressionNode')]
final class IssetExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var ExpressionResult[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $varResults;
    /**
     * @param ExpressionResult[] $varResults
     */
    public function __construct(
        Isset_ $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $varResults
    )
    {
        $this->varResults = $varResults;
        parent::__construct($originalNode->getAttributes());
    }
    /**
     * @return ExpressionResult[]
     */
    public function getVarResults(): array
    {
        return $this->varResults;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_IssetExpressionNode';
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
