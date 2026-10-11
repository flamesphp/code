<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Equal;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Isset_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
final readonly class FromBinaryAndAssertExpressionsFactory
{
    public function __construct(private NodeFactory $nodeFactory, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param Expr[] $exprs
     * @return Stmt[]
     */
    public function create(array $exprs, bool $isStaticClosure = \false): array
    {
        $assertMethodCalls = [];
        foreach ($exprs as $expr) {
            // implicit bool compare
            if ($expr instanceof MethodCall) {
                $assertMethodCalls[] = $this->createAssertMethodCall($isStaticClosure, 'assertTrue', [$expr]);
                continue;
            }
            if ($expr instanceof FuncCall && $this->nodeNameResolver->isName($expr, 'array_key_exists')) {
                $variableExpr = $expr->getArgs()[1]->value;
                $dimExpr = $expr->getArgs()[0]->value;
                $assertMethodCalls[] = $this->createAssertMethodCall($isStaticClosure, 'assertArrayHasKey', [$dimExpr, $variableExpr]);
                continue;
            }
            if ($expr instanceof Isset_) {
                foreach ($expr->vars as $issetVariable) {
                    if ($issetVariable instanceof ArrayDimFetch && $issetVariable->dim instanceof Expr) {
                        $assertMethodCalls[] = $this->createAssertMethodCall($isStaticClosure, 'assertArrayHasKey', [$issetVariable->dim, $issetVariable->var]);
                    } else {
                        // not supported yet
                        return [];
                    }
                }
                continue;
            }
            if ($expr instanceof Instanceof_) {
                if ($expr->class instanceof FullyQualified) {
                    $classNameExpr = new ClassConstFetch(new FullyQualified($expr->class->name), 'class');
                } elseif ($expr->class instanceof Name) {
                    $classNameExpr = new ClassConstFetch(new Name($expr->class->name), 'class');
                } else {
                    $classNameExpr = $expr->class;
                }
                $assertMethodCalls[] = $this->createAssertMethodCall($isStaticClosure, 'assertInstanceOf', [$classNameExpr, $expr->expr]);
                continue;
            }
            if ($expr instanceof Identical || $expr instanceof Equal) {
                if ($expr->left instanceof FuncCall && $this->nodeNameResolver->isName($expr->left, 'count')) {
                    if ($expr->right instanceof Int_) {
                        $countedExpr = $expr->left->getArgs()[0]->value;
                        // create assertCount()
                        $assertMethodCalls[] = $this->createAssertMethodCall($isStaticClosure, 'assertCount', [$expr->right, $countedExpr]);
                        continue;
                    }
                    // unclear, fallback to no change
                    return [];
                }
                // create assertSame()
                $assertMethodCalls[] = $this->createAssertMethodCall($isStaticClosure, $expr instanceof Identical ? 'assertSame' : 'assertEquals', [$expr->right, $expr->left]);
            } else {
                // not supported expr
                return [];
            }
        }
        if ($assertMethodCalls === []) {
            return [];
        }
        // to keep order from binary
        $assertMethodCalls = array_reverse($assertMethodCalls);
        $stmts = [];
        foreach ($assertMethodCalls as $assertMethodCall) {
            $stmts[] = new Expression($assertMethodCall);
        }
        return $stmts;
    }
    /**
     * @param Expr[] $args
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
     */
    private function createAssertMethodCall(bool $isStaticClosure, string $method, array $args)
    {
        if ($isStaticClosure) {
            return new StaticCall(new Name('self'), $method, $this->nodeFactory->createArgs($args));
        }
        return $this->nodeFactory->createMethodCall('this', $method, $args);
    }
}
