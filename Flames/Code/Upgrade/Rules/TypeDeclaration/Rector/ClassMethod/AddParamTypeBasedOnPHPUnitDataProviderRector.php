<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\MixedType;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer\ParameterTypeFromDataProviderResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\ValueObject\DataProviderNodes;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\DataProviderMethodsFinder;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamTypeBasedOnPHPUnitDataProviderRectorTest
 */
final class AddParamTypeBasedOnPHPUnitDataProviderRector extends AbstractRector
{
    private const string ERROR_MESSAGE = 'Adds param type declaration based on PHPUnit provider return type declaration';
    public function __construct(private readonly TestsNodeAnalyzer $testsNodeAnalyzer, private readonly DataProviderMethodsFinder $dataProviderMethodsFinder, private readonly ParameterTypeFromDataProviderResolver $parameterTypeFromDataProviderResolver, private readonly StaticTypeMapper $staticTypeMapper)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [new CodeSample(<<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test($value)
    {
    }

    public static function provideData()
    {
        yield ['name'];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class SomeTest extends TestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $value)
    {
    }

    public static function provideData()
    {
        yield ['name'];
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
        if (!$this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->isPublic()) {
                continue;
            }
            if ($classMethod->getParams() === []) {
                continue;
            }
            $dataProviderNodes = $this->dataProviderMethodsFinder->findDataProviderNodes($node, $classMethod);
            if ($dataProviderNodes->getClassMethods() === []) {
                continue;
            }
            $hasClassMethodChanged = $this->refactorClassMethod($classMethod, $dataProviderNodes);
            if ($hasClassMethodChanged) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function refactorClassMethod(ClassMethod $classMethod, DataProviderNodes $dataProviderNodes): bool
    {
        $hasChanged = \false;
        foreach ($classMethod->getParams() as $parameterPosition => $param) {
            if ($param->type instanceof Node) {
                continue;
            }
            if ($param->variadic) {
                continue;
            }
            $paramTypeDeclaration = $this->parameterTypeFromDataProviderResolver->resolve($parameterPosition, $dataProviderNodes->getClassMethods());
            if ($paramTypeDeclaration instanceof MixedType) {
                continue;
            }
            $typeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($paramTypeDeclaration, TypeKind::PARAM);
            if ($typeNode instanceof Node) {
                $param->type = $typeNode;
                $hasChanged = \true;
            }
        }
        return $hasChanged;
    }
}
