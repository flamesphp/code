<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeFactory;

use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final readonly class NamedVariableFactory
{
    public function __construct(private VariableNaming $variableNaming)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\Expression|\PhpParser\Node\Expr\Ternary $expression
     */
    public function createVariable(string $variableName, $expression): Variable
    {
        $scope = $expression->getAttribute(AttributeKey::SCOPE);
        $variableName = $this->variableNaming->createCountedValueName($variableName, $scope);
        return new Variable($variableName);
    }
}
