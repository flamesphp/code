<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\Param;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ComplexType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php84\Rector\Param\ExplicitNullableParamTypeRectorTest
 */
final class ExplicitNullableParamTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ValueResolver $valueResolver, private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Make implicit nullable param to explicit', [new CodeSample(<<<'CODE_SAMPLE'
function foo(string $param = null) {}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function foo(?string $param = null) {}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Param::class];
    }
    /**
     * @param Param $node
     */
    public function refactor(Node $node): ?Param
    {
        if (!$node->type instanceof Node) {
            return null;
        }
        if (!$node->default instanceof ConstFetch || !$this->valueResolver->isNull($node->default)) {
            return null;
        }
        $nodeType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($node->type);
        if (TypeCombinator::containsNull($nodeType)) {
            return null;
        }
        // mixed can't be nullable, ref https://3v4l.org/YUkhH/rfc#vgit.master
        if ($nodeType instanceof MixedType) {
            return null;
        }
        $newNodeType = TypeCombinator::addNull($nodeType);
        $paramType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($newNodeType, TypeKind::PARAM);
        // ensure it process valid Node, otherwise, just return null
        if (!$paramType instanceof Node) {
            return null;
        }
        // re-use existing node instead of reprint Node that may cause unnecessary FQCN
        if ($node->type instanceof UnionType) {
            $node->type->types[] = new Name('null');
        } elseif ($node->type instanceof ComplexType) {
            /** @var IntersectionType $nodeType */
            $nodeType = $node->type;
            $node->type = new UnionType([$nodeType, new Name('null')]);
        } else {
            $node->type = new NullableType($node->type);
        }
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_IMPLICIT_NULLABLE_PARAM_TYPE;
    }
}
