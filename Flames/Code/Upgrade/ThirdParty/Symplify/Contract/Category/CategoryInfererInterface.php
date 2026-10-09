<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symplify\Contract\Category;

use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
interface CategoryInfererInterface
{
    public function infer(RuleDefinition $ruleDefinition): ?string;
}
