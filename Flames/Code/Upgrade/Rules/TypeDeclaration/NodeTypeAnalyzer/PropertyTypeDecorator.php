<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeTypeAnalyzer;

use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
final readonly class PropertyTypeDecorator
{
    public function __construct(private PhpDocTypeChanger $phpDocTypeChanger, private PhpVersionProvider $phpVersionProvider, private NodeFactory $nodeFactory)
    {
    }
    /**
     * @param \PhpParser\Node\Name|\PhpParser\Node\ComplexType|\PhpParser\Node\Identifier $typeNode
     */
    public function decoratePropertyUnionType(UnionType $unionType, $typeNode, Property $property, PhpDocInfo $phpDocInfo, bool $changeVarTypeFallback = \true): void
    {
        if (!TypeCombinator::containsNull($unionType)) {
            if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::UNION_TYPES)) {
                $property->type = $typeNode;
                return;
            }
            if ($changeVarTypeFallback) {
                $this->phpDocTypeChanger->changeVarType($property, $phpDocInfo, $unionType);
            }
            return;
        }
        $property->type = $typeNode;
        $propertyProperty = $property->props[0];
        // add null default
        if (!$propertyProperty->default instanceof Expr) {
            $propertyProperty->default = $this->nodeFactory->createNull();
        }
        // has array with defined type? add docs
        if (!$this->isDocBlockRequired($unionType)) {
            return;
        }
        if (!$changeVarTypeFallback) {
            return;
        }
        $this->phpDocTypeChanger->changeVarType($property, $phpDocInfo, $unionType);
    }
    private function isDocBlockRequired(UnionType $unionType): bool
    {
        foreach ($unionType->getTypes() as $unionedType) {
            if ($unionedType->isArray()->yes()) {
                $describedArray = $unionedType->describe(VerbosityLevel::value());
                if ($describedArray !== 'array') {
                    return \true;
                }
            }
        }
        return \false;
    }
}
