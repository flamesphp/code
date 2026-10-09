<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configuration\Parameter;

use PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Configuration\Option;
/**
 * Class to manage feature flags,
 * that loosen or tighten the behavior of Upgrade rules.
 */
final class FeatureFlags
{
    public static function treatClassesAsFinal(Class_ $class): bool
    {
        // abstract class never can be treated as "final"
        // as always must be overridden
        if ($class->isAbstract()) {
            return \false;
        }
        return \Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider::provideBoolParameter(Option::TREAT_CLASSES_AS_FINAL);
    }
}
