<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\YieldNodeFinder;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TypeResolver\YieldTypeResolver;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\FunctionLike\AddReturnTypeDeclarationFromYieldsRectorTest
 */
final class AddReturnTypeDeclarationFromYieldsRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly StaticTypeMapper $staticTypeMapper, private readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private readonly YieldNodeFinder $yieldNodeFinder, private readonly YieldTypeResolver $yieldTypeResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return type declarations from yields', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function provide()
    {
        yield 1;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    /**
     * @return Iterator<int>
     */
    public function provide(): Iterator
    {
        yield 1;
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
        return [Function_::class, ClassMethod::class];
    }
    /**
     * @param Function_|ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        $yieldNodes = $this->yieldNodeFinder->find($node);
        if ($yieldNodes === []) {
            return null;
        }
        // skip already filled type
        if ($node->returnType instanceof Node && $this->isNames($node->returnType, ['Iterator', 'Generator', 'Traversable', 'iterable'])) {
            return null;
        }
        if ($node instanceof ClassMethod && $this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope)) {
            return null;
        }
        $yieldType = $this->yieldTypeResolver->resolveFromYieldNodes($yieldNodes, $node);
        $returnTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($yieldType, TypeKind::RETURN);
        if (!$returnTypeNode instanceof Node) {
            return null;
        }
        $node->returnType = $returnTypeNode;
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SCALAR_TYPES;
    }
}
