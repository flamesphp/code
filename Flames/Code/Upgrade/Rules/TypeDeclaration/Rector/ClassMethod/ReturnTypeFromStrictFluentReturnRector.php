<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\ReturnTypeInferer;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\NodeVendorLocker\ClassMethodReturnTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRectorTest
 */
final class ReturnTypeFromStrictFluentReturnRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ClassMethodReturnTypeOverrideGuard $classMethodReturnTypeOverrideGuard, private readonly ReflectionResolver $reflectionResolver, private readonly ReturnTypeInferer $returnTypeInferer, private readonly PhpVersionProvider $phpVersionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return type from strict return $this', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        return $this;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(): self
    {
        return $this;
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
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::HAS_RETURN_TYPE;
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        // already typed → skip
        if ($node->returnType instanceof Node) {
            return null;
        }
        if ($this->classMethodReturnTypeOverrideGuard->shouldSkipClassMethod($node, $scope)) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $returnType = $this->returnTypeInferer->inferFunctionLike($node);
        if ($returnType instanceof StaticType && $returnType->getStaticObjectType()->getClassName() === $classReflection->getName()) {
            return $this->processAddReturnSelfOrStatic($node, $classReflection);
        }
        if ($returnType instanceof ObjectType && $returnType->getClassName() === $classReflection->getName()) {
            $node->returnType = new Name('self');
            return $node;
        }
        if (!$returnType instanceof ThisType) {
            return null;
        }
        return $this->processAddReturnSelfOrStatic($node, $classReflection);
    }
    private function processAddReturnSelfOrStatic(ClassMethod $classMethod, ClassReflection $classReflection): ClassMethod
    {
        $classMethod->returnType = $this->shouldSelf($classReflection) ? new Name('self') : new Name('static');
        return $classMethod;
    }
    private function shouldSelf(ClassReflection $classReflection): bool
    {
        if ($classReflection->isAnonymous()) {
            return \true;
        }
        if ($classReflection->isFinalByKeyword()) {
            return \true;
        }
        return !$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::STATIC_RETURN_TYPE);
    }
}
