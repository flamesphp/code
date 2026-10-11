<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symfony\Contract;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
interface EventReferenceToMethodNameInterface
{
    public function getClassConstFetch(): ClassConstFetch;
    public function getMethodName(): string;
}
