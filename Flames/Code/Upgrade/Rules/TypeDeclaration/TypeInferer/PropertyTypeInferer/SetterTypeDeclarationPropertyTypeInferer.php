<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\PropertyTypeInferer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ClassMethodAndPropertyAnalyzer;
final readonly class SetterTypeDeclarationPropertyTypeInferer
{
    public function __construct(private ClassMethodAndPropertyAnalyzer $classMethodAndPropertyAnalyzer, private NodeNameResolver $nodeNameResolver, private StaticTypeMapper $staticTypeMapper)
    {
    }
    public function inferProperty(Property $property, Class_ $class): ?Type
    {
        $propertyName = $this->nodeNameResolver->getName($property);
        foreach ($class->getMethods() as $classMethod) {
            if (!$this->classMethodAndPropertyAnalyzer->hasOnlyPropertyAssign($classMethod, $propertyName)) {
                continue;
            }
            $paramTypeNode = $classMethod->params[0]->type ?? null;
            if (!$paramTypeNode instanceof Node) {
                return null;
            }
            $paramType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($paramTypeNode);
            // let PhpDoc solve that later for more precise type
            if ($paramType->isArray()->yes()) {
                return new MixedType();
            }
            if (!$paramType instanceof MixedType) {
                return $paramType;
            }
        }
        return null;
    }
}
