<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Configuration\Parameter\FeatureFlags;
final class PrivateMethodFlagger
{
    public function isClassMethodPrivate(Class_ $class, ClassMethod $classMethod): bool
    {
        if ($classMethod->isPrivate()) {
            return \true;
        }
        if ($classMethod->isFinal() && !$class->extends instanceof Name && $class->implements === []) {
            return \true;
        }
        $isClassFinal = $class->isFinal() || FeatureFlags::treatClassesAsFinal($class);
        return $isClassFinal && !$class->extends instanceof Name && $class->implements === [] && $classMethod->isProtected();
    }
}
