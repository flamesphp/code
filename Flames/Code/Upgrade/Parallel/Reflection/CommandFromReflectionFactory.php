<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Parallel\Reflection;

use Flames\Code\Upgrade\Parallel\Exception\ParallelShouldNotHappenException;
use ReflectionClass;
use ReflectionMethod;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Command\Command;
final class CommandFromReflectionFactory
{
    /**
     * @param class-string<Command> $className
     */
    public function create(string $className): Command
    {
        $commandReflectionClass = new ReflectionClass($className);
        $command = $commandReflectionClass->newInstanceWithoutConstructor();
        $parentClassReflection = $commandReflectionClass->getParentClass();
        if (!$parentClassReflection instanceof ReflectionClass) {
            throw new ParallelShouldNotHappenException();
        }
        $parentConstructorReflectionMethod = $parentClassReflection->getConstructor();
        if (!$parentConstructorReflectionMethod instanceof ReflectionMethod) {
            throw new ParallelShouldNotHappenException();
        }
        $parentConstructorReflectionMethod->invoke($command);
        return $command;
    }
}
