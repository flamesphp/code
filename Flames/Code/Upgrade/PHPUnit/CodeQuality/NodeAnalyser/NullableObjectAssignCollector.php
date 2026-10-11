<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeAnalyser;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableNameToType;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject\VariableNameToTypeCollection;
/**
 * We look for object|null type on the left:
 *
 * $value = $this->getSomething();
 */
final readonly class NullableObjectAssignCollector
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private NodeTypeResolver $nodeTypeResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_ $stmtsAware
     */
    public function collect($stmtsAware): VariableNameToTypeCollection
    {
        $variableNamesToType = [];
        // first round to collect assigns
        foreach ((array) $stmtsAware->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                return new VariableNameToTypeCollection([]);
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            $variableNameToType = $this->collectFromAssign($stmt->expr);
            if (!$variableNameToType instanceof VariableNameToType) {
                continue;
            }
            $variableNamesToType[] = $variableNameToType;
        }
        return new VariableNameToTypeCollection($variableNamesToType);
    }
    private function collectFromAssign(Assign $assign): ?VariableNameToType
    {
        if (!$assign->expr instanceof MethodCall) {
            return null;
        }
        if (!$assign->var instanceof Variable) {
            return null;
        }
        $variableType = $this->nodeTypeResolver->getType($assign);
        $bareVariableType = TypeCombinator::removeNull($variableType);
        if (!$bareVariableType instanceof ObjectType) {
            return null;
        }
        $variableName = $this->nodeNameResolver->getName($assign->var);
        if (!is_string($variableName)) {
            return null;
        }
        return new VariableNameToType($variableName, $bareVariableType->getClassName());
    }
}
