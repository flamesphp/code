<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ArrowFunction;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRectorTest
 */
final class AddArrowFunctionReturnTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add known return type to arrow function', [new CodeSample(<<<'CODE_SAMPLE'
fn () => [];
CODE_SAMPLE
, <<<'CODE_SAMPLE'
fn (): array => [];
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ArrowFunction::class];
    }
    /**
     * @param ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->returnType instanceof Node) {
            return null;
        }
        // to allow array shape
        $type = $node->expr instanceof ArrayDimFetch ? $this->getType($node->expr) : $this->nodeTypeResolver->getNativeType($node->expr);
        // not valid to add explicit type in PHP
        if ($type->isVoid()->yes()) {
            return null;
        }
        $docblockType = $this->getType($node->expr);
        if ($type instanceof MixedType && $docblockType instanceof NullType) {
            return null;
        }
        $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::RETURN);
        if (!$returnTypeNode instanceof Node) {
            return null;
        }
        $node->returnType = $returnTypeNode;
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ARROW_FUNCTION;
    }
}
