<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
/**
 * @api used in deprecated ScalarValueToConstFetchUpgrade configs
 */
final class ScalarValueToConstFetch
{
    /**
     * @param \PhpParser\Node\Scalar\Float_|\PhpParser\Node\Scalar\String_|\PhpParser\Node\Scalar\Int_ $scalar
     * @param \PhpParser\Node\Expr\ConstFetch|\PhpParser\Node\Expr\ClassConstFetch $constFetch
     */
    public function __construct(
        /**
         * @readonly
         */
        private $scalar,
        /**
         * @readonly
         */
        private $constFetch
    )
    {
    }
    /**
     * @return \PhpParser\Node\Scalar\Float_|\PhpParser\Node\Scalar\String_|\PhpParser\Node\Scalar\Int_
     */
    public function getScalar()
    {
        return $this->scalar;
    }
    /**
     * @return \PhpParser\Node\Expr\ConstFetch|\PhpParser\Node\Expr\ClassConstFetch
     */
    public function getConstFetch()
    {
        return $this->constFetch;
    }
}
