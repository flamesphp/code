<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\NarrowBoolDocblockReturnTypeRectorTest
 */
final class NarrowBoolDocblockReturnTypeRector extends AbstractRector
{
    public function __construct(private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Narrow @return docblock "bool" to "false" or "true" when the native return type only allows one of them', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @return bool|string[]
     */
    public function run(): false|array
    {
        return false;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @return false|string[]
     */
    public function run(): false|array
    {
        return false;
    }
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class];
    }
    /**
     * @param ClassMethod|Function_ $node
     * @return null|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_
     */
    public function refactor(Node $node)
    {
        $constantBoolName = $this->matchNativeConstantBoolName($node);
        if ($constantBoolName === null) {
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
        if (!$returnTagValueNode->type instanceof UnionTypeNode) {
            return null;
        }
        if (!$this->narrowBoolInUnion($returnTagValueNode->type, $constantBoolName)) {
            return null;
        }
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    /**
     * Returns "false" or "true" when the native return type allows exactly one of them, null otherwise.
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $node
     */
    private function matchNativeConstantBoolName($node): ?string
    {
        $returnType = $node->returnType;
        if (!$returnType instanceof UnionType) {
            return null;
        }
        $hasFalse = \false;
        $hasTrue = \false;
        foreach ($returnType->types as $type) {
            if (!$type instanceof Identifier) {
                continue;
            }
            $lowerName = $type->toLowerString();
            if ($lowerName === 'bool') {
                // ambiguous, both values possible
                return null;
            }
            if ($lowerName === 'false') {
                $hasFalse = \true;
            }
            if ($lowerName === 'true') {
                $hasTrue = \true;
            }
        }
        if ($hasFalse && !$hasTrue) {
            return 'false';
        }
        if ($hasTrue && !$hasFalse) {
            return 'true';
        }
        return null;
    }
    private function narrowBoolInUnion(UnionTypeNode $unionTypeNode, string $constantBoolName): bool
    {
        // already contains the constant? replacing "bool" would create a duplicate
        foreach ($unionTypeNode->types as $typeNode) {
            if ($typeNode instanceof IdentifierTypeNode && $typeNode->name === $constantBoolName) {
                return \false;
            }
        }
        $hasChanged = \false;
        foreach ($unionTypeNode->types as $key => $typeNode) {
            if ($typeNode instanceof IdentifierTypeNode && $typeNode->name === 'bool') {
                $unionTypeNode->types[$key] = new IdentifierTypeNode($constantBoolName);
                $hasChanged = \true;
            }
        }
        return $hasChanged;
    }
}
