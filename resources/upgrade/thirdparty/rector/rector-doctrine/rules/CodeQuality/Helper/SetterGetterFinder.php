<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\CodeQuality\Helper;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ClassMethodAndPropertyAnalyzer;
final readonly class SetterGetterFinder
{
    public function __construct(private ClassMethodAndPropertyAnalyzer $classMethodAndPropertyAnalyzer)
    {
    }
    public function findGetterClassMethod(Class_ $class, string $propertyName): ?ClassMethod
    {
        foreach ($class->getMethods() as $classMethod) {
            if (!$this->classMethodAndPropertyAnalyzer->hasPropertyFetchReturn($classMethod, $propertyName)) {
                continue;
            }
            return $classMethod;
        }
        return null;
    }
    public function findSetterClassMethod(Class_ $class, string $propertyName): ?ClassMethod
    {
        foreach ($class->getMethods() as $classMethod) {
            if (!$this->classMethodAndPropertyAnalyzer->hasOnlyPropertyAssign($classMethod, $propertyName)) {
                continue;
            }
            return $classMethod;
        }
        return null;
    }
}
