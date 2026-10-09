<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\Contract;

use PhpParser\Node\Expr\ClassConstFetch;
interface EventReferenceToMethodNameInterface
{
    public function getClassConstFetch(): ClassConstFetch;
    public function getMethodName(): string;
}
