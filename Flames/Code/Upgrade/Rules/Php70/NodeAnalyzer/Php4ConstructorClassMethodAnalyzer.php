<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
final class Php4ConstructorClassMethodAnalyzer
{
    public function detect(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        if ($classMethod->isAbstract()) {
            return \false;
        }
        if ($classMethod->isStatic()) {
            return \false;
        }
        if ($classReflection->isAnonymous()) {
            return \false;
        }
        $possiblePhp4MethodNames = [$classReflection->getName(), lcfirst($classReflection->getName())];
        return in_array($classMethod->name->toString(), $possiblePhp4MethodNames, \true);
    }
}
