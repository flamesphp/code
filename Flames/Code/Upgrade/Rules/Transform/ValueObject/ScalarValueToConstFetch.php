<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
/**
 * @api used in deprecated ScalarValueToConstFetchUpgrade configs
 */
final class ScalarValueToConstFetch
{
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_ $scalar
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch $constFetch
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
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_
     */
    public function getScalar()
    {
        return $this->scalar;
    }
    /**
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch
     */
    public function getConstFetch()
    {
        return $this->constFetch;
    }
}
