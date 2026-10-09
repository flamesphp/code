<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\CodeQuality\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
final class ArgAndFunctionLike
{
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
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
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction
     */
    public function getFunctionLike()
    {
        return $this->functionLike;
    }
}
