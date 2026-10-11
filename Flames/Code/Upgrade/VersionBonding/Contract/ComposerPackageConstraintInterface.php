<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\VersionBonding\Contract;

use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
/**
 * Can be implemented by @see \Flames\Code\Upgrade\Contract\Rector\RectorInterface
 *
 * Rules that do not meet this composer package constraint will be skipped.
 *
 * @api used by extensions
 */
interface ComposerPackageConstraintInterface
{
    /**
     * Return a single constraint, or a list of constraints that must all be satisfied at once,
     * e.g. an attribute that only works when both a library and its framework integration are new enough.
     *
     * @return ComposerPackageConstraint|list<ComposerPackageConstraint>
     */
    public function provideComposerPackageConstraint();
}
