<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Contract\Rector;

use Symplify\RuleDocGenerator\Contract\ConfigurableRuleInterface;
interface ConfigurableRectorInterface extends \Flames\Code\Upgrade\Contract\Rector\RectorInterface, ConfigurableRuleInterface
{
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void;
}
