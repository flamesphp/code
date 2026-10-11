<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('originalForeachValueExpr')]
final class OriginalForeachValueExpr extends Expr implements VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $variableName;
    public Expr\Variable $var;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $variableName
    )
    {
        $this->variableName = $variableName;
        parent::__construct([]);
        $this->var = new Expr\Variable($this->variableName);
    }
    public function getVariableName(): string
    {
        return $this->variableName;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_OriginalForeachValueExpr';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return ['var'];
    }
}
