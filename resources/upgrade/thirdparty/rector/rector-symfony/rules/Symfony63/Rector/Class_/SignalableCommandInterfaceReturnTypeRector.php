<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony63\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Symfony\NodeAnalyzer\ClassAnalyzer;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\ComposerPackageConstraintInterface;
use Flames\Code\Upgrade\VersionBonding\ValueObject\ComposerPackageConstraint;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Symfony63\Rector\Class_\SignalableCommandInterfaceReturnTypeRectorTest
 */
final class SignalableCommandInterfaceReturnTypeRector extends AbstractRector implements ComposerPackageConstraintInterface
{
    public function __construct(private readonly ClassAnalyzer $classAnalyzer, private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function provideComposerPackageConstraint(): ComposerPackageConstraint
    {
        return new ComposerPackageConstraint('symfony/console', '>=6.3');
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Return int or false from SignalableCommandInterface::handleSignal() instead of void', [new CodeSample(<<<'CODE_SAMPLE'
    public function handleSignal(int $signal): void
    {
    }
CODE_SAMPLE
, <<<'CODE_SAMPLE'

    public function handleSignal(int $signal): int|false
    {
        return false;
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
    public function refactor(Node $node): ?Node
    {
        if (!$this->classAnalyzer->hasImplements($node, 'Symfony\Component\Console\Command\SignalableCommandInterface')) {
            return null;
        }
        $classMethod = $node->getMethod('handleSignal');
        if (!$classMethod instanceof ClassMethod) {
            return null;
        }
        $unionType = new UnionType([new IntegerType(), new ConstantBooleanType(\false)]);
        if ($this->parentClassMethodTypeOverrideGuard->shouldSkipReturnTypeChange($classMethod, $unionType)) {
            return null;
        }
        $classMethod->returnType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($unionType, TypeKind::RETURN);
        $classMethod->stmts[] = new Return_($this->nodeFactory->createFalse());
        return $node;
    }
}
