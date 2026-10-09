<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\ValueObject;

use PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\Symfony\Contract\EventReferenceToMethodNameInterface;
final readonly class EventReferenceToMethodName implements EventReferenceToMethodNameInterface
{
    public function __construct(private ClassConstFetch $classConstFetch, private string $methodName)
    {
    }
    public function getClassConstFetch(): ClassConstFetch
    {
        return $this->classConstFetch;
    }
    public function getMethodName(): string
    {
        return $this->methodName;
    }
}
