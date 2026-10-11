<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\Exception\NotImplementedYetException;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFactory\NestedClosureAssertFactory;
use Flames\Code\Upgrade\PHPUnit\Enum\ConsecutiveVariable;
final readonly class ConsecutiveIfsFactory
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private NestedClosureAssertFactory $nestedClosureAssertFactory)
    {
    }
    /**
     * @return Stmt[]
     */
    public function createIfs(MethodCall $withConsecutiveMethodCall, MethodCall $numberOfInvocationsMethodCall): array
    {
        $ifs = [];
        $parametersVariable = new Variable(ConsecutiveVariable::PARAMETERS);
        foreach ($withConsecutiveMethodCall->getArgs() as $key => $withConsecutiveArg) {
            $ifStmts = [];
            if ($withConsecutiveArg->value instanceof Array_) {
                $array = $withConsecutiveArg->value;
                foreach ($array->items as $assertKey => $assertArrayItem) {
                    if (!$assertArrayItem->value instanceof MethodCall) {
                        $parametersDimFetch = new ArrayDimFetch(new Variable('parameters'), new Int_($assertKey));
                        $args = [new Arg($assertArrayItem->value), new Arg($parametersDimFetch)];
                        $ifStmts[] = new Expression(new MethodCall(new Variable('this'), 'assertSame', $args));
                        continue;
                    }
                    $assertMethodCall = $assertArrayItem->value;
                    if ($this->nodeNameResolver->isName($assertMethodCall->name, 'equalTo')) {
                        $ifStmts[] = $this->createAssertMethodCall($assertMethodCall, $parametersVariable, $assertKey);
                    } elseif ($this->nodeNameResolver->isName($assertMethodCall->name, 'callback')) {
                        $ifStmts = array_merge($ifStmts, $this->nestedClosureAssertFactory->create($assertMethodCall, $assertKey));
                    } else {
                        $args = [new Arg($assertMethodCall), new Arg(new ArrayDimFetch(new Variable('parameters'), new Int_($assertKey)))];
                        $assertSameMethodCall = new MethodCall(new Variable('this'), new Identifier('assertSame'), $args);
                        $ifStmts[] = new Expression($assertSameMethodCall);
                    }
                }
            } elseif ($withConsecutiveArg->value instanceof MethodCall) {
                $methodCall = $withConsecutiveArg->value;
                if ($this->nodeNameResolver->isName($methodCall->name, 'callback')) {
                    // special callable case
                    $firstArg = $methodCall->getArgs()[0];
                    if ($firstArg->value instanceof ArrowFunction) {
                        $arrowFunction = $firstArg->value;
                        if ($arrowFunction->expr instanceof Identical) {
                            $identicalCompare = $arrowFunction->expr;
                            // @todo improve in time
                            if ($identicalCompare->left instanceof Variable) {
                                $parametersArrayDimFetch = new ArrayDimFetch(new Variable('parameters'), new Int_(0));
                                $assertSameMethodCall = new MethodCall(new Variable('this'), new Identifier('assertSame'));
                                $assertSameMethodCall->args[] = new Arg($identicalCompare->right);
                                $assertSameMethodCall->args[] = new Arg($parametersArrayDimFetch);
                                return [new Expression($assertSameMethodCall)];
                            }
                        }
                    }
                }
                throw new NotImplementedYetException();
            }
            $ifs[] = new If_(new Identical($numberOfInvocationsMethodCall, new Int_($key + 1)), ['stmts' => $ifStmts]);
        }
        return $ifs;
    }
    private function createAssertMethodCall(MethodCall $assertMethodCall, Variable $parametersVariable, int $parameterPositionKey): Expression
    {
        $assertMethodCall->name = new Identifier('assertEquals');
        $parametersArrayDimFetch = new ArrayDimFetch($parametersVariable, new Int_($parameterPositionKey));
        $assertMethodCall->args[] = new Arg($parametersArrayDimFetch);
        return new Expression($assertMethodCall);
    }
}
