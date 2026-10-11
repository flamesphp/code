<?php

declare (strict_types=1);
namespace PHPStan\Dependency;

use PHPStan\Reflection\ClassReflection;
/**
 * What DependencyResolver::resolveFileDependencies() resolved the dependencies of an analysed file to.
 */
final class ResolvedDependencies
{
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $fileDependencies;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $usedTraitFileDependencies;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $packageDependencies;
    /**
     * @var list<ClassReflection>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $classReflections;
    /**
     * @param list<string> $fileDependencies
     * @param list<string> $usedTraitFileDependencies
     * @param list<string> $packageDependencies
     * @param list<ClassReflection> $classReflections every class depended on, with its ancestors
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $fileDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $usedTraitFileDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $packageDependencies,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $classReflections
    )
    {
        $this->fileDependencies = $fileDependencies;
        $this->usedTraitFileDependencies = $usedTraitFileDependencies;
        $this->packageDependencies = $packageDependencies;
        $this->classReflections = $classReflections;
    }
    /**
     * @return list<string>
     */
    public function getFileDependencies(): array
    {
        return $this->fileDependencies;
    }
    /**
     * @return list<string>
     */
    public function getUsedTraitFileDependencies(): array
    {
        return $this->usedTraitFileDependencies;
    }
    /**
     * @return list<string>
     */
    public function getPackageDependencies(): array
    {
        return $this->packageDependencies;
    }
    /**
     * @return list<ClassReflection>
     */
    public function getClassReflections(): array
    {
        return $this->classReflections;
    }
}
