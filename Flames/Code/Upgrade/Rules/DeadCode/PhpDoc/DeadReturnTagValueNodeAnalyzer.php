<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\ThisTypeNode;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\Guard\StandaloneTypeRemovalGuard;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\Guard\TemplateTypeRemovalGuard;
use Flames\Code\Upgrade\Rules\DeadCode\TypeNodeAnalyzer\GenericTypeNodeAnalyzer;
use Flames\Code\Upgrade\Rules\DeadCode\TypeNodeAnalyzer\MixedArrayTypeNodeAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class DeadReturnTagValueNodeAnalyzer
{
    public function __construct(private TypeComparator $typeComparator, private GenericTypeNodeAnalyzer $genericTypeNodeAnalyzer, private MixedArrayTypeNodeAnalyzer $mixedArrayTypeNodeAnalyzer, private StandaloneTypeRemovalGuard $standaloneTypeRemovalGuard, private PhpDocTypeChanger $phpDocTypeChanger, private StaticTypeMapper $staticTypeMapper, private TemplateTypeRemovalGuard $templateTypeRemovalGuard)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    public function isDead(ReturnTagValueNode $returnTagValueNode, $functionLike): bool
    {
        $returnType = $functionLike->getReturnType();
        if ($returnType === null) {
            return \false;
        }
        if ($returnTagValueNode->description !== '') {
            return \false;
        }
        if ($returnTagValueNode->type instanceof GenericTypeNode) {
            return \false;
        }
        $docType = $this->staticTypeMapper->mapPHPStanPhpDocTypeNodeToPHPStanType($returnTagValueNode->type, $functionLike);
        if (!$this->templateTypeRemovalGuard->isLegal($docType)) {
            return \false;
        }
        $scope = $functionLike->getAttribute(AttributeKey::SCOPE);
        if ($scope instanceof Scope && $scope->isInTrait() && $returnTagValueNode->type instanceof ThisTypeNode) {
            return \false;
        }
        if (!$this->typeComparator->arePhpParserAndPhpStanPhpDocTypesEqual($returnType, $returnTagValueNode->type, $functionLike)) {
            return $this->isDeadNotEqual($returnTagValueNode, $returnType, $functionLike);
        }
        if ($this->phpDocTypeChanger->isAllowed($returnTagValueNode->type)) {
            return \false;
        }
        if (!$returnTagValueNode->type instanceof BracketsAwareUnionTypeNode) {
            return $this->standaloneTypeRemovalGuard->isLegal($returnTagValueNode->type, $returnType);
        }
        if ($this->genericTypeNodeAnalyzer->hasGenericType($returnTagValueNode->type)) {
            return \false;
        }
        if ($this->mixedArrayTypeNodeAnalyzer->hasMixedArrayType($returnTagValueNode->type)) {
            return \false;
        }
        return !$this->hasTrueFalsePseudoType($returnTagValueNode->type);
    }
    private function isVoidReturnType(Node $node): bool
    {
        return $node instanceof Identifier && $node->toString() === 'void';
    }
    private function isNeverReturnType(Node $node): bool
    {
        return $node instanceof Identifier && $node->toString() === 'never';
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     */
    private function isDeadNotEqual(ReturnTagValueNode $returnTagValueNode, Node $node, $functionLike): bool
    {
        if ($returnTagValueNode->type instanceof IdentifierTypeNode && (string) $returnTagValueNode->type === 'void') {
            return \true;
        }
        if (!$this->hasUsefulPhpdocType($returnTagValueNode, $node)) {
            return \true;
        }
        $nodeType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($node);
        $docType = $this->staticTypeMapper->mapPHPStanPhpDocTypeNodeToPHPStanType($returnTagValueNode->type, $functionLike);
        return $docType instanceof UnionType && $this->typeComparator->areTypesEqual(TypeCombinator::removeNull($docType), $nodeType);
    }
    private function hasTrueFalsePseudoType(BracketsAwareUnionTypeNode $bracketsAwareUnionTypeNode): bool
    {
        $unionTypes = $bracketsAwareUnionTypeNode->types;
        foreach ($unionTypes as $unionType) {
            if (!$unionType instanceof IdentifierTypeNode) {
                continue;
            }
            $name = strtolower((string) $unionType);
            if (in_array($name, ['true', 'false'], \true)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * exact different between @return and node return type
     * @param mixed $returnType
     */
    private function hasUsefulPhpdocType(ReturnTagValueNode $returnTagValueNode, $returnType): bool
    {
        if ($returnTagValueNode->type instanceof IdentifierTypeNode && $returnTagValueNode->type->name === 'mixed') {
            return \false;
        }
        if (!$this->isVoidReturnType($returnType)) {
            return !$this->isNeverReturnType($returnType);
        }
        if (!$returnTagValueNode->type instanceof IdentifierTypeNode || (string) $returnTagValueNode->type !== 'never') {
            return \false;
        }
        return !$this->isNeverReturnType($returnType);
    }
}
