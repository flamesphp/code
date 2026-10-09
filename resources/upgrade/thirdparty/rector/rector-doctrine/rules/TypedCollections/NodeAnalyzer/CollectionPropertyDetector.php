<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\TypedCollections\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\UnionType;
use Flames\Code\Upgrade\Doctrine\Enum\DoctrineClass;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class CollectionPropertyDetector
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function detect(Property $property): bool
    {
        if (!$property->type instanceof Node) {
            return \false;
        }
        // 1. direct type
        if ($this->nodeNameResolver->isName($property->type, DoctrineClass::COLLECTION)) {
            return \true;
        }
        // 2. union type
        if ($property->type instanceof UnionType) {
            $unionType = $property->type;
            foreach ($unionType->types as $unionedType) {
                if ($this->nodeNameResolver->isName($unionedType, DoctrineClass::COLLECTION)) {
                    return \true;
                }
            }
        }
        // 3. nullable type
        if ($property->type instanceof NullableType) {
            $directType = $property->type->type;
            if ($this->nodeNameResolver->isName($directType, DoctrineClass::COLLECTION)) {
                return \true;
            }
        }
        return \false;
    }
}
