<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRectorTest
 */
final class AddReturnTypeDeclarationBasedOnParentClassMethodRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly PhpVersionProvider $phpVersionProvider, private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SCALAR_TYPES;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add missing return type declaration based on parent class method', [new CodeSample(<<<'CODE_SAMPLE'
class A
{
    public function execute(): int
    {
    }
}

class B extends A{
    public function execute()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class A
{
    public function execute(): int
    {
    }
}

class B extends A{
    public function execute(): int
    {
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($this->isNames($classMethod, [MethodName::CONSTRUCT, MethodName::DESTRUCT])) {
                continue;
            }
            $parentClassMethodReturnType = $this->getReturnTypeRecursive($classMethod);
            if (!$parentClassMethodReturnType instanceof Type) {
                continue;
            }
            $changedClassMethod = $this->processClassMethodReturnType($node, $classMethod, $parentClassMethodReturnType);
            if (!$changedClassMethod instanceof ClassMethod) {
                continue;
            }
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function getReturnTypeRecursive(ClassMethod $classMethod): ?Type
    {
        $returnType = $classMethod->getReturnType();
        if ($returnType instanceof Node) {
            return $this->staticTypeMapper->mapPhpParserNodePHPStanType($returnType);
        }
        $parentMethodReflection = $this->parentClassMethodTypeOverrideGuard->getParentClassMethod($classMethod);
        while ($parentMethodReflection instanceof MethodReflection) {
            if ($parentMethodReflection->isPrivate()) {
                return null;
            }
            $parameterAcceptor = ParametersAcceptorSelector::combineAcceptors($parentMethodReflection->getVariants());
            $parentReturnType = $parameterAcceptor->getNativeReturnType();
            if (!$parentReturnType instanceof MixedType) {
                return $parentReturnType;
            }
            if ($parentReturnType->isExplicitMixed()) {
                return $parentReturnType;
            }
            $parentMethodReflection = $this->parentClassMethodTypeOverrideGuard->getParentClassMethod($parentMethodReflection);
        }
        return null;
    }
    private function processClassMethodReturnType(Class_ $class, ClassMethod $classMethod, Type $parentType): ?ClassMethod
    {
        if ($parentType instanceof MixedType) {
            $className = (string) $this->getName($class);
            $currentObjectType = new ObjectType($className);
            if (!$parentType->equals($currentObjectType) && $classMethod->returnType instanceof Node) {
                return null;
            }
        }
        if ($parentType instanceof MixedType && !$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::MIXED_TYPE)) {
            return null;
        }
        // already set and sub type or equal → no change
        if ($this->parentClassMethodTypeOverrideGuard->shouldSkipReturnTypeChange($classMethod, $parentType)) {
            return null;
        }
        $classMethod->returnType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($parentType, TypeKind::RETURN);
        return $classMethod;
    }
}
