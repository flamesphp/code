<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php83\Rector\ClassConst;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Configuration\Parameter\FeatureFlags;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover\VarTagRemover;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php83\Rector\ClassConst\AddTypeToConstRectorTest
 */
final class AddTypeToConstRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider, private readonly StaticTypeMapper $staticTypeMapper, private readonly VarTagRemover $varTagRemover, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add type to constants based on their value', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public const TYPE = 'some_type';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public const string TYPE = 'some_type';
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Class_
    {
        $className = $this->getName($node);
        if (!is_string($className)) {
            return null;
        }
        $classConsts = $node->getConstants();
        if ($classConsts === []) {
            return null;
        }
        $parentClassReflections = $this->getParentReflections($className);
        $hasChanged = \false;
        foreach ($classConsts as $classConst) {
            $valueTypes = [];
            // If a type is set, skip
            if ($classConst->type !== null) {
                continue;
            }
            foreach ($classConst->consts as $constNode) {
                if ($node->isAbstract() && !$classConst->isPrivate()) {
                    continue;
                }
                if ($this->isConstGuardedByParents($constNode, $parentClassReflections)) {
                    continue;
                }
                if ($this->canBeInherited($classConst, $node)) {
                    continue;
                }
                $valueTypes[] = $this->findValueType($constNode->value);
            }
            if ($valueTypes === []) {
                continue;
            }
            if (count($valueTypes) > 1) {
                $valueTypes = array_unique($valueTypes, \SORT_REGULAR);
            }
            // once more verify after uniquate
            if (count($valueTypes) > 1) {
                continue;
            }
            $valueType = current($valueTypes);
            if (!$valueType instanceof Identifier) {
                continue;
            }
            $classConst->type = $valueType;
            $hasChanged = \true;
            $classConstPhpDocInfo = $this->phpDocInfoFactory->createFromNode($classConst);
            if ($classConstPhpDocInfo instanceof PhpDocInfo) {
                $this->varTagRemover->removeVarTagIfUseless($classConstPhpDocInfo, $classConst);
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_CLASS_CONSTANTS;
    }
    /**
     * @param ClassReflection[] $parentClassReflections
     */
    public function isConstGuardedByParents(Const_ $const, array $parentClassReflections): bool
    {
        $constantName = $this->getName($const);
        $found = array_any($parentClassReflections, fn($parentClassReflection) => $parentClassReflection->hasConstant($constantName));
        return $found;
    }
    private function findValueType(Expr $expr): ?Identifier
    {
        if ($expr instanceof UnaryPlus || $expr instanceof UnaryMinus) {
            return $this->findValueType($expr->expr);
        }
        if ($expr instanceof String_) {
            return new Identifier('string');
        }
        if ($expr instanceof Int_) {
            return new Identifier('int');
        }
        if ($expr instanceof Float_) {
            return new Identifier('float');
        }
        if ($expr instanceof ConstFetch || $expr instanceof ClassConstFetch) {
            if ($expr instanceof ConstFetch && $expr->name->toLowerString() === 'null') {
                return new Identifier('null');
            }
            $type = $this->nodeTypeResolver->getNativeType($expr);
            $nodeType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PROPERTY);
            if (!$nodeType instanceof Identifier) {
                return null;
            }
            return $nodeType;
        }
        if ($expr instanceof Array_) {
            return new Identifier('array');
        }
        if ($expr instanceof Concat) {
            return new Identifier('string');
        }
        return null;
    }
    /**
     * @return ClassReflection[]
     */
    private function getParentReflections(string $className): array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }
        $currentClassReflection = $this->reflectionProvider->getClass($className);
        return array_filter($currentClassReflection->getAncestors(), static fn(ClassReflection $classReflection): bool => $currentClassReflection !== $classReflection);
    }
    private function canBeInherited(ClassConst $classConst, Class_ $class): bool
    {
        if (FeatureFlags::treatClassesAsFinal($class)) {
            return \false;
        }
        return !$class->isFinal() && !$classConst->isPrivate() && !$classConst->isFinal();
    }
}
