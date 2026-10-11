<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use PHPStan\Type\Constant\ConstantArrayType;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\ReturnNodeFinder;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TagNodeAnalyzer\UsefulArrayTagNodeAnalyzer;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TypeResolver\ConstantArrayTypeGeneralizer;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\Rector\ClassMethod\DocblockReturnArrayFromDirectArrayInstanceRectorTest
 */
final class DocblockReturnArrayFromDirectArrayInstanceRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly PhpDocTypeChanger $phpDocTypeChanger, private readonly ConstantArrayTypeGeneralizer $constantArrayTypeGeneralizer, private readonly ReturnNodeFinder $returnNodeFinder, private readonly UsefulArrayTagNodeAnalyzer $usefulArrayTagNodeAnalyzer, private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard)
    {
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add simple @return array docblock based on direct single level direct return of []', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function getItems(): array
    {
        return [
            'hey' => 'now',
        ];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @return array<string, string>
     */
    public function getItems(): array
    {
        return [
            'hey' => 'now',
        ];
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }
        // skip overridden methods to not conflict with parent/interface @return docblock
        if ($node instanceof ClassMethod && $this->parentClassMethodTypeOverrideGuard->hasParentClassMethod($node)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        if ($this->usefulArrayTagNodeAnalyzer->isUsefulArrayTag($phpDocInfo->getReturnTagValue())) {
            return null;
        }
        $soleReturn = $this->returnNodeFinder->findOnlyReturnWithExpr($node);
        if (!$soleReturn instanceof Return_) {
            return null;
        }
        if (!$soleReturn->expr instanceof Array_) {
            return null;
        }
        if ($this->shouldSkipEmptyArray($phpDocInfo, $soleReturn->expr)) {
            return null;
        }
        // bare "@return array" with "return []" -> "mixed[]", better than "array{}"
        if ($soleReturn->expr->items === [] && $this->hasBareArrayReturnTag($phpDocInfo)) {
            $this->phpDocTypeChanger->changeReturnTypeNode($node, $phpDocInfo, new SpacingAwareArrayTypeNode(new IdentifierTypeNode('mixed')));
            return $node;
        }
        // resolve simple type
        $returnedType = $this->getType($soleReturn->expr);
        if (!$returnedType instanceof ConstantArrayType) {
            return null;
        }
        // better handled by shared-interface/class rule, to avoid turning objects to mixed
        if ($returnedType->getReferencedClasses() !== [] && count($returnedType->getReferencedClasses()) === count($returnedType->getValueTypes())) {
            return null;
        }
        $genericTypeNode = $this->constantArrayTypeGeneralizer->generalize($returnedType);
        $this->phpDocTypeChanger->changeReturnTypeNode($node, $phpDocInfo, $genericTypeNode);
        return $node;
    }
    private function shouldSkipEmptyArray(PhpDocInfo $phpDocInfo, Array_ $array): bool
    {
        if ($array->items !== []) {
            return \false;
        }
        // skip empty array; @return array{} is too narrow, except refining bare "array" to "mixed[]" is still useful
        return !$this->hasBareArrayReturnTag($phpDocInfo);
    }
    private function hasBareArrayReturnTag(PhpDocInfo $phpDocInfo): bool
    {
        $returnTagValueNode = $phpDocInfo->getReturnTagValue();
        if (!$returnTagValueNode instanceof ReturnTagValueNode) {
            return \false;
        }
        return $returnTagValueNode->type instanceof IdentifierTypeNode && $returnTagValueNode->type->name === 'array';
    }
}
