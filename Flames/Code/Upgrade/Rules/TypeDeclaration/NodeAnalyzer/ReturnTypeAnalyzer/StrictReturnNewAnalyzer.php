<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnTypeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
use Flames\Code\Upgrade\Rules\TypeDeclaration\ValueObject\AssignToVariable;
final readonly class StrictReturnNewAnalyzer
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeNameResolver $nodeNameResolver, private NodeTypeResolver $nodeTypeResolver, private ReturnAnalyzer $returnAnalyzer)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function matchAlwaysReturnVariableNew($functionLike): ?string
    {
        if ($functionLike->stmts === null) {
            return null;
        }
        $returns = $this->betterNodeFinder->findReturnsScoped($functionLike);
        if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($functionLike, $returns)) {
            return null;
        }
        // in case of more returns, we need to check if they all return the same variable
        $createdVariablesToTypes = $this->resolveCreatedVariablesToTypes($functionLike);
        $alwaysReturnedClassNames = [];
        foreach ($returns as $return) {
            // exact one return of variable
            if (!$return->expr instanceof Variable) {
                return null;
            }
            $returnType = $this->nodeTypeResolver->getNativeType($return->expr);
            if ($returnType instanceof ObjectWithoutClassType) {
                $alwaysReturnedClassNames[] = 'object';
                continue;
            }
            if (!$returnType instanceof ObjectType) {
                return null;
            }
            $returnedVariableName = $this->nodeNameResolver->getName($return->expr);
            $className = $createdVariablesToTypes[$returnedVariableName] ?? null;
            if (!is_string($className)) {
                return null;
            }
            if ($returnType->getClassName() !== $className) {
                return null;
            }
            $alwaysReturnedClassNames[] = $className;
        }
        $uniqueAlwaysReturnedClasses = array_unique($alwaysReturnedClassNames);
        if (count($uniqueAlwaysReturnedClasses) !== 1) {
            return null;
        }
        return $uniqueAlwaysReturnedClasses[0];
    }
    /**
     * @return array<string, string>
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    private function resolveCreatedVariablesToTypes($functionLike): array
    {
        $createdVariablesToTypes = [];
        // what new is assigned to it?
        foreach ((array) $functionLike->stmts as $stmt) {
            $assignToVariable = $this->matchAssignToVariable($stmt);
            if (!$assignToVariable instanceof AssignToVariable) {
                continue;
            }
            $assignedExpr = $assignToVariable->getAssignedExpr();
            $variableName = $assignToVariable->getVariableName();
            if (!$assignedExpr instanceof New_) {
                // possible variable override by another type! - unset it
                if (isset($createdVariablesToTypes[$variableName])) {
                    unset($createdVariablesToTypes[$variableName]);
                }
                continue;
            }
            $className = $this->nodeNameResolver->getName($assignedExpr->class);
            if (!is_string($className)) {
                continue;
            }
            $createdVariablesToTypes[$variableName] = $className;
        }
        return $createdVariablesToTypes;
    }
    private function matchAssignToVariable(Stmt $stmt): ?AssignToVariable
    {
        if (!$stmt instanceof Expression) {
            return null;
        }
        if (!$stmt->expr instanceof Assign) {
            return null;
        }
        $assign = $stmt->expr;
        $assignedVar = $assign->var;
        if (!$assignedVar instanceof Variable) {
            return null;
        }
        $variableName = $this->nodeNameResolver->getName($assignedVar);
        if (!is_string($variableName)) {
            return null;
        }
        return new AssignToVariable($variableName, $assign->expr);
    }
}
