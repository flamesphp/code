<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Contract\Rector;

/**
 * Upgrade rule with this marker interface will skip all files
 * with any HTML node. This is practical to avoid malformed PHP+HTML files
 */
interface HTMLAverseRectorInterface
{
}
