<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\Guard\StandaloneTypeRemovalGuard;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\Guard\TemplateTypeRemovalGuard;
use Flames\Code\Upgrade\Rules\DeadCode\TypeNodeAnalyzer\GenericTypeNodeAnalyzer;
use Flames\Code\Upgrade\Rules\DeadCode\TypeNodeAnalyzer\MixedArrayTypeNodeAnalyzer;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ParamAnalyzer;
final readonly class DeadParamTagValueNodeAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private TypeComparator $typeComparator, private GenericTypeNodeAnalyzer $genericTypeNodeAnalyzer, private MixedArrayTypeNodeAnalyzer $mixedArrayTypeNodeAnalyzer, private ParamAnalyzer $paramAnalyzer, private PhpDocTypeChanger $phpDocTypeChanger, private StandaloneTypeRemovalGuard $standaloneTypeRemovalGuard, private StaticTypeMapper $staticTypeMapper, private TemplateTypeRemovalGuard $templateTypeRemovalGuard)
    {
    }
    public function isDead(ParamTagValueNode $paramTagValueNode, FunctionLike $functionLike): bool
    {
        $param = $this->paramAnalyzer->getParamByName($paramTagValueNode->parameterName, $functionLike);
        if (!$param instanceof Param) {
            return \false;
        }
        if (!$param->type instanceof Node) {
            return \false;
        }
        if ($paramTagValueNode->description !== '') {
            return \false;
        }
        if ($paramTagValueNode->type instanceof GenericTypeNode) {
            return \false;
        }
        $docType = $this->staticTypeMapper->mapPHPStanPhpDocTypeNodeToPHPStanType($paramTagValueNode->type, $functionLike);
        if (!$this->templateTypeRemovalGuard->isLegal($docType)) {
            return \false;
        }
        if ($param->type instanceof Name && $this->nodeNameResolver->isName($param->type, 'object')) {
            return $paramTagValueNode->type instanceof IdentifierTypeNode && (string) $paramTagValueNode->type === 'object';
        }
        if (!$this->typeComparator->arePhpParserAndPhpStanPhpDocTypesEqual($param->type, $paramTagValueNode->type, $functionLike)) {
            return \false;
        }
        if ($this->phpDocTypeChanger->isAllowed($paramTagValueNode->type)) {
            return \false;
        }
        if (!$paramTagValueNode->type instanceof BracketsAwareUnionTypeNode) {
            return $this->standaloneTypeRemovalGuard->isLegal($paramTagValueNode->type, $param->type);
        }
        return $this->isAllowedBracketAwareUnion($paramTagValueNode->type);
    }
    private function isAllowedBracketAwareUnion(BracketsAwareUnionTypeNode $bracketsAwareUnionTypeNode): bool
    {
        if ($this->mixedArrayTypeNodeAnalyzer->hasMixedArrayType($bracketsAwareUnionTypeNode)) {
            return \false;
        }
        return !$this->genericTypeNodeAnalyzer->hasGenericType($bracketsAwareUnionTypeNode);
    }
}
