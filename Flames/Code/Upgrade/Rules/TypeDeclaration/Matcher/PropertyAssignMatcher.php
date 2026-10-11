<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Matcher;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
final readonly class PropertyAssignMatcher
{
    public function __construct(private PropertyFetchAnalyzer $propertyFetchAnalyzer)
    {
    }
    /**
     * Covers:
     * - $this->propertyName = $expr;
     * - $this->propertyName[] = $expr;
     */
    public function matchPropertyAssignExpr(Assign $assign, string $propertyName): ?Expr
    {
        $assignVar = $assign->var;
        if ($this->propertyFetchAnalyzer->isLocalPropertyFetchName($assignVar, $propertyName)) {
            return $assign->expr;
        }
        if (!$assignVar instanceof ArrayDimFetch) {
            return null;
        }
        if ($this->propertyFetchAnalyzer->isLocalPropertyFetchName($assignVar->var, $propertyName)) {
            return $assign->expr;
        }
        return null;
    }
}
