<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Symplify\Contract;

use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @api
 */
interface DocumentedRuleInterface
{
    public function getRuleDefinition(): RuleDefinition;
}
