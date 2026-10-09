<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\NodeAnalyzer;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Symfony\DataProvider\ServiceMapProvider;
final readonly class ServiceTypeMethodCallResolver
{
    public function __construct(private ServiceMapProvider $serviceMapProvider, private NodeNameResolver $nodeNameResolver)
    {
    }
    public function resolve(MethodCall $methodCall): ?Type
    {
        if (!isset($methodCall->args[0])) {
            return new MixedType();
        }
        $firstArg = $methodCall->getArgs()[0];
        $argument = $firstArg->value;
        $serviceMap = $this->serviceMapProvider->provide();
        if ($argument instanceof String_) {
            return $serviceMap->getServiceType($argument->value);
        }
        if ($argument instanceof ClassConstFetch && $argument->class instanceof Name) {
            $className = $this->nodeNameResolver->getName($argument->class);
            return new ObjectType($className);
        }
        return new MixedType();
    }
}
