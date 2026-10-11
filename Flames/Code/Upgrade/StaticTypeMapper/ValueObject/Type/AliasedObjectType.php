<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
/**
 * @api
 */
final class AliasedObjectType extends ObjectType
{
    public function __construct(string $alias, private readonly string $fullyQualifiedClass)
    {
        parent::__construct($alias);
    }
    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedClass;
    }
    /**
     * @param Use_::TYPE_* $useType
     */
    public function getUseNode(int $useType): Use_
    {
        $name = new Name($this->fullyQualifiedClass);
        $useItem = new UseItem($name, $this->getClassName());
        $use = new Use_([$useItem]);
        $use->type = $useType;
        return $use;
    }
    public function getShortName(): string
    {
        return $this->getClassName();
    }
    /**
     * @param $this|\Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType $comparedObjectType
     */
    public function areShortNamesEqual($comparedObjectType): bool
    {
        return $this->getShortName() === $comparedObjectType->getShortName();
    }
    public function equals(Type $type): bool
    {
        $className = ClassNameFromObjectTypeResolver::resolve($type);
        // compare with FQN classes
        if ($className !== null) {
            if ($type instanceof self && $this->fullyQualifiedClass === $type->getFullyQualifiedName()) {
                return \true;
            }
            if ($this->fullyQualifiedClass === $className) {
                return \true;
            }
        }
        return parent::equals($type);
    }
}
