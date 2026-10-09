<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\ValueObject\AddParamTypeDeclaration;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRectorTest
 */
final class AddParamTypeDeclarationRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var AddParamTypeDeclaration[]
     */
    private array $addParamTypeDeclarations = [];
    private bool $hasChanged = \false;
    public function __construct(private readonly TypeComparator $typeComparator, private readonly PhpVersionProvider $phpVersionProvider, private readonly StaticTypeMapper $staticTypeMapper, private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add param types where needed', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function process($name)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function process(string $name)
    {
    }
}
CODE_SAMPLE
, [new AddParamTypeDeclaration('SomeClass', 'process', 0, new StringType())])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Interface_::class];
    }
    /**
     * @param Class_|Interface_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $this->hasChanged = \false;
        // skip guarded classes, where adding a param type would break child classes
        if ($this->parentClassMethodTypeOverrideGuard->isTypeGuardedClass($node)) {
            return null;
        }
        foreach ($node->getMethods() as $classMethod) {
            if ($this->shouldSkip($node, $classMethod)) {
                continue;
            }
            $methodName = $this->getName($classMethod);
            foreach ($this->addParamTypeDeclarations as $addParamTypeDeclaration) {
                if (!$this->nodeNameResolver->isStringName($methodName, $addParamTypeDeclaration->getMethodName())) {
                    continue;
                }
                if (!$this->isObjectType($node, $addParamTypeDeclaration->getObjectType())) {
                    continue;
                }
                $this->refactorClassMethodWithTypehintByParameterPosition($classMethod, $addParamTypeDeclaration);
            }
        }
        if (!$this->hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, AddParamTypeDeclaration::class);
        $this->addParamTypeDeclarations = $configuration;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_ $classLike
     */
    private function shouldSkip($classLike, ClassMethod $classMethod): bool
    {
        // skip class methods without args
        if ($classMethod->params === []) {
            return \true;
        }
        // skip class without parents/interfaces
        if ($classLike instanceof Class_ && $classLike->implements !== []) {
            return \false;
        }
        return !$classLike->extends instanceof Name;
    }
    private function refactorClassMethodWithTypehintByParameterPosition(ClassMethod $classMethod, AddParamTypeDeclaration $addParamTypeDeclaration): void
    {
        $parameter = $classMethod->params[$addParamTypeDeclaration->getPosition()] ?? null;
        if (!$parameter instanceof Param) {
            return;
        }
        $this->refactorParameter($parameter, $addParamTypeDeclaration);
    }
    private function refactorParameter(Param $param, AddParamTypeDeclaration $addParamTypeDeclaration): void
    {
        // already set → no change
        if ($param->type instanceof Node) {
            $currentParamType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($param->type);
            if ($this->typeComparator->areTypesEqual($currentParamType, $addParamTypeDeclaration->getParamType())) {
                return;
            }
        }
        $paramTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($addParamTypeDeclaration->getParamType(), TypeKind::PARAM);
        $this->hasChanged = \true;
        // remove it
        if ($addParamTypeDeclaration->getParamType() instanceof MixedType) {
            if ($this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::MIXED_TYPE)) {
                $param->type = $paramTypeNode;
                return;
            }
            $param->type = null;
            return;
        }
        $param->type = $paramTypeNode;
    }
}
