<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddParamTypeSplFixedArrayRectorTest
 */
final class AddParamTypeSplFixedArrayRector extends AbstractRector
{
    /**
     * @var array<string, string>
     */
    private const array SPL_FIXED_ARRAY_TO_SINGLE = ['PhpCsFixer\Tokenizer\Tokens' => 'PhpCsFixer\Tokenizer\Token', 'PhpCsFixer\Doctrine\Annotation\Tokens' => 'PhpCsFixer\Doctrine\Annotation\Token'];
    public function __construct(private readonly PhpDocTypeChanger $phpDocTypeChanger, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Function_::class, ClassMethod::class];
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add exact fixed array type in known cases', [new CodeSample(<<<'CODE_SAMPLE'
use PhpCsFixer\Tokenizer\Tokens;

class SomeClass
{
    public function run(Tokens $tokens)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

class SomeClass
{
    /**
     * @param Tokens<Token>
     */
    public function run(Tokens $tokens)
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @param FunctionLike $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->getParams() === []) {
            return null;
        }
        $functionLikePhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $hasChanged = \false;
        foreach ($node->getParams() as $param) {
            if ($param->type === null) {
                continue;
            }
            $paramType = $this->nodeTypeResolver->getType($param->type);
            if ($paramType->isSuperTypeOf(new ObjectType('SplFixedArray'))->no()) {
                continue;
            }
            if (!$paramType instanceof TypeWithClassName) {
                continue;
            }
            if ($paramType instanceof GenericObjectType) {
                continue;
            }
            $genericParamType = $this->resolveGenericType($paramType);
            if (!$genericParamType instanceof Type) {
                continue;
            }
            $paramName = $this->getName($param);
            $changedParamType = $this->phpDocTypeChanger->changeParamType($node, $functionLikePhpDocInfo, $genericParamType, $param, $paramName);
            if ($changedParamType) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function resolveGenericType(TypeWithClassName $typeWithClassName): ?\PHPStan\Type\Generic\GenericObjectType
    {
        foreach (self::SPL_FIXED_ARRAY_TO_SINGLE as $fixedArrayClass => $singleClass) {
            if ($typeWithClassName->getClassName() === $fixedArrayClass) {
                $genericObjectType = new ObjectType($singleClass);
                return new GenericObjectType($typeWithClassName->getClassName(), [$genericObjectType]);
            }
        }
        return null;
    }
}
