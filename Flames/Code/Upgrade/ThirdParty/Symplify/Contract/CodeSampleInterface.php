<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symplify\Contract;

interface CodeSampleInterface
{
    public function getGoodCode(): string;
    public function getBadCode(): string;
}
