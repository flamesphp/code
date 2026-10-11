<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\PHPUnit\Enum\PHPUnitClassName;
final class AssertArrayHasKeyCallFactory
{
    public function create(Variable $dimFetchVariable, Expr $dimExpr, Scope $scope): Expression
    {
        $args = $this->createArgs($dimFetchVariable, $dimExpr);
        if ($this->isInsideTestCase($scope)) {
            $call = new MethodCall(new Variable('this'), 'assertArrayHasKey', $args);
            return new Expression($call);
        }
        $call = new StaticCall(new FullyQualified(PHPUnitClassName::ASSERT), 'assertArrayHasKey', $args);
        return new Expression($call);
    }
    private function isInsideTestCase(Scope $scope): bool
    {
        if (!$scope->isInClass()) {
            return \false;
        }
        $classReflection = $scope->getClassReflection();
        return $classReflection->is(PHPUnitClassName::TEST_CASE);
    }
    /**
     * @return Arg[]
     */
    private function createArgs(Variable $dimFetchVariable, Expr $dimExpr): array
    {
        // add detailed error: 'Existing keys are: ' . implode(', ', array_keys($data))
        $arrayKeysFuncCall = new FuncCall(new Name('array_keys'), [new Arg($dimFetchVariable)]);
        $implodeFuncCall = new FuncCall(new Name('implode'), [new Arg(new String_(', ')), new Arg($arrayKeysFuncCall)]);
        $concat = new Concat(new String_('Existing keys are: '), $implodeFuncCall);
        return [new Arg($dimExpr), new Arg($dimFetchVariable), new Arg($concat)];
    }
}
