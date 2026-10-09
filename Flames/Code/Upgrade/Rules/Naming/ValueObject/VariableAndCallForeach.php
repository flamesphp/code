<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
final class VariableAndCallForeach
{
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall $expr
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    public function __construct(
        private readonly Variable $variable,
        /**
         * @readonly
         */
        private $expr,
        private readonly string $variableName,
        /**
         * @readonly
         */
        private $functionLike
    )
    {
    }
    public function getVariable(): Variable
    {
        return $this->variable;
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall
     */
    public function getCall()
    {
        return $this->expr;
    }
    public function getVariableName(): string
    {
        return $this->variableName;
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_
     */
    public function getFunctionLike()
    {
        return $this->functionLike;
    }
}
