<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\ExpectedNameResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rules\Naming\Naming\PropertyNaming;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\ExpectedName;
use Flames\Code\Upgrade\NodeManipulator\PropertyManipulator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class MatchPropertyTypeExpectedNameResolver
{
    public function __construct(private PropertyNaming $propertyNaming, private PhpDocInfoFactory $phpDocInfoFactory, private NodeNameResolver $nodeNameResolver, private PropertyManipulator $propertyManipulator, private ReflectionResolver $reflectionResolver, private StaticTypeMapper $staticTypeMapper)
    {
    }
    public function resolve(Property $property, ClassLike $classLike): ?string
    {
        if (!$classLike instanceof Class_) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($property);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $propertyName = $this->nodeNameResolver->getName($property);
        if ($this->propertyManipulator->isUsedByTrait($classReflection, $propertyName)) {
            return null;
        }
        $expectedName = $this->resolveExpectedName($property);
        if (!$expectedName instanceof ExpectedName) {
            return null;
        }
        // skip if already has suffix
        if (str_ends_with($propertyName, $expectedName->getName()) || str_ends_with($propertyName, ucfirst($expectedName->getName()))) {
            return null;
        }
        return $expectedName->getName();
    }
    private function resolveExpectedName(Property $property): ?ExpectedName
    {
        // property type first
        if ($property->type instanceof Node) {
            $propertyType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($property->type);
            return $this->propertyNaming->getExpectedNameFromType($propertyType);
        }
        // fallback to docblock
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($property);
        $hasVarTag = $phpDocInfo instanceof PhpDocInfo && $phpDocInfo->getVarTagValueNode() instanceof VarTagValueNode;
        if ($hasVarTag) {
            return $this->propertyNaming->getExpectedNameFromType($phpDocInfo->getVarType());
        }
        return null;
    }
}
