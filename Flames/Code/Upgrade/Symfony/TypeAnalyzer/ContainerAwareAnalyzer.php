<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\TypeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\Symfony\Enum\SymfonyClass;
final readonly class ContainerAwareAnalyzer
{
    /**
     * @var ObjectType[]
     */
    private array $getMethodAwareObjectTypes;
    public function __construct(private NodeTypeResolver $nodeTypeResolver)
    {
        $this->getMethodAwareObjectTypes = [new ObjectType(SymfonyClass::ABSTRACT_CONTROLLER), new ObjectType(SymfonyClass::CONTROLLER), new ObjectType(SymfonyClass::CONTROLLER_TRAIT)];
    }
    public function isGetMethodAwareType(Expr $expr): bool
    {
        return $this->nodeTypeResolver->isObjectTypes($expr, $this->getMethodAwareObjectTypes);
    }
}
