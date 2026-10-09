<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
final class ArgAndFunctionLike
{
    /**
     * @param \PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    public function __construct(
        private readonly Arg $arg,
        /**
         * @readonly
         */
        private $functionLike
    )
    {
    }
    public function getArg(): Arg
    {
        return $this->arg;
    }
    /**
     * @return \PhpParser\Node\Expr\Closure|\PhpParser\Node\Expr\ArrowFunction
     */
    public function getFunctionLike()
    {
        return $this->functionLike;
    }
}
