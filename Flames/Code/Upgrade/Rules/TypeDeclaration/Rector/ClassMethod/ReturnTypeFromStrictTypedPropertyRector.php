<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\ReturnAnalyzer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRectorTest
 */
final class ReturnTypeFromStrictTypedPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly TypeFactory $typeFactory, private readonly ReflectionResolver $reflectionResolver, private readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private readonly BetterNodeFinder $betterNodeFinder, private readonly StaticTypeMapper $staticTypeMapper, private readonly ReturnAnalyzer $returnAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return method return type based on strict typed property', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    private int $age = 100;

    public function getAge()
    {
        return $this->age;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private int $age = 100;

    public function getAge(): int
    {
        return $this->age;
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
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->returnType instanceof Node) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        if ($this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope)) {
            return null;
        }
        $propertyTypes = $this->resolveReturnPropertyType($node);
        if ($propertyTypes === []) {
            return null;
        }
        // add type to return type
        $propertyType = $this->typeFactory->createMixedPassedOrUnionType($propertyTypes);
        if ($propertyType instanceof MixedType) {
            return null;
        }
        $propertyTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($propertyType, TypeKind::RETURN);
        if (!$propertyTypeNode instanceof Node) {
            return null;
        }
        $node->returnType = $propertyTypeNode;
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES;
    }
    /**
     * @return Type[]
     */
    private function resolveReturnPropertyType(ClassMethod $classMethod): array
    {
        $returns = $this->betterNodeFinder->findReturnsScoped($classMethod);
        $propertyTypes = [];
        foreach ($returns as $return) {
            if (!$return->expr instanceof Expr) {
                return [];
            }
            if (!$return->expr instanceof PropertyFetch && !$return->expr instanceof StaticPropertyFetch) {
                return [];
            }
            $phpPropertyReflection = $this->reflectionResolver->resolvePropertyReflectionFromPropertyFetch($return->expr);
            if (!$phpPropertyReflection instanceof PhpPropertyReflection) {
                return [];
            }
            // all property must have type declaration
            if ($phpPropertyReflection->getNativeType() instanceof MixedType) {
                return [];
            }
            $propertyTypes[] = $this->nodeTypeResolver->getNativeType($return->expr);
        }
        if (!$this->returnAnalyzer->hasOnlyReturnWithExpr($classMethod, $returns)) {
            return [];
        }
        return $propertyTypes;
    }
}
