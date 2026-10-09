<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
final readonly class SymfonyTestCaseAnalyzer
{
    public function __construct(private ReflectionResolver $reflectionResolver)
    {
    }
    /**
     * @api
     */
    public function isInKernelTestCase(Node $node): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        return $classReflection->is('Symfony\Bundle\FrameworkBundle\Test\KernelTestCase');
    }
}
