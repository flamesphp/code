<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\TypeAliasImportTag;
use PHPStan\PhpDoc\Tag\TypeAliasTag;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node as AstNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\ThisTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveReturnTagIncompatibleWithNativeTypeRectorTest
 */
final class RemoveReturnTagIncompatibleWithNativeTypeRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove @return docblock that contradicts the declared native return type', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    /**
     * @return SomeObject
     */
    public function getName(): string
    {
        return $this->someObject->getName();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function getName(): string
    {
        return $this->someObject->getName();
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }
    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // no native return type to compare against
        if ($node->returnType === null) {
            return null;
        }
        // nothing to remove
        if ($node->getComments() === []) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $returnTagValueNode = $phpDocInfo->getReturnTagValue();
        if (!$returnTagValueNode instanceof ReturnTagValueNode) {
            return null;
        }
        // keep any documented explanation
        if ($returnTagValueNode->description !== '') {
            return null;
        }
        // keep generic return types, because they carry template info the native type cannot express
        if ($returnTagValueNode->type instanceof GenericTypeNode) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        if ($this->isClassTypeAlias($scope, $returnTagValueNode)) {
            return null;
        }
        $nativeReturnType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($node->returnType);
        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection && $classReflection->isTrait() && $returnTagValueNode->type instanceof ThisTypeNode && $nativeReturnType instanceof ObjectType) {
            return null;
        }
        if ($this->isReturnTemplate($phpDocInfo, $returnTagValueNode)) {
            return null;
        }
        $docReturnType = $phpDocInfo->getReturnType();
        // a subtype/narrowing is legitimate; only a contradiction is dead
        if (!$nativeReturnType->isSuperTypeOf($docReturnType)->no()) {
            return null;
        }
        if (!$phpDocInfo->removeByType(ReturnTagValueNode::class)) {
            return null;
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    private function isClassTypeAlias(Scope $scope, ReturnTagValueNode $returnTagValueNode): bool
    {
        if (!$scope->isInClass()) {
            return \false;
        }
        $resolvedPhpDocBlock = $scope->getClassReflection()->getResolvedPhpDoc();
        if (!$resolvedPhpDocBlock instanceof ResolvedPhpDocBlock) {
            return \false;
        }
        $typeAliases = $resolvedPhpDocBlock->getTypeAliasTags() + $resolvedPhpDocBlock->getTypeAliasImportTags();
        if ($typeAliases === []) {
            return \false;
        }
        return $this->containsTypeAliasName($returnTagValueNode->type, $typeAliases);
    }
    /**
     * The alias can be nested in a composed type as well, e.g. "ConfigArray|CustomConfig" or "?ConfigArray"
     *
     * @param array<string, TypeAliasTag|TypeAliasImportTag> $typeAliases
     */
    private function containsTypeAliasName(TypeNode $typeNode, array $typeAliases): bool
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            return isset($typeAliases[$typeNode->name]);
        }
        $hasTypeAliasName = \false;
        // the traverser visits sub-nodes only, that is why the type node itself is checked above
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->traverseWithCallable($typeNode, '', static function (AstNode $astNode) use ($typeAliases, &$hasTypeAliasName): ?int {
            if ($astNode instanceof IdentifierTypeNode && isset($typeAliases[$astNode->name])) {
                $hasTypeAliasName = \true;
                return PhpDocNodeTraverser::STOP_TRAVERSAL;
            }
            return null;
        });
        return $hasTypeAliasName;
    }
    private function isReturnTemplate(PhpDocInfo $phpDocInfo, ReturnTagValueNode $returnTagValueNode): bool
    {
        if (!$returnTagValueNode->type instanceof IdentifierTypeNode) {
            return \false;
        }
        return in_array($returnTagValueNode->type->name, $phpDocInfo->getTemplateNames(), \true);
    }
}
