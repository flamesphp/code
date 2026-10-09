<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
use Flames\Code\Upgrade\Rules\Php81\NodeManipulator\NullToStrictStringIntConverter;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see https://wiki.php.net/rfc/deprecations_php_8_5#deprecate_using_values_null_as_an_array_offset_and_when_calling_array_key_exists
 * @see \Flames\Code\Upgrade\Rules\Php85\Rector\FuncCall\ArrayKeyExistsNullToEmptyStringRectorTest
 */
final class ArrayKeyExistsNullToEmptyStringRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly NullToStrictStringIntConverter $nullToStrictStringIntConverter, private readonly ArgsAnalyzer $argsAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace null key in array_key_exists with empty string', [new CodeSample(<<<'CODE_SAMPLE'
array_key_exists(null, $array);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
array_key_exists('', $array);
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        if (!$this->isName($node, 'array_key_exists')) {
            return null;
        }
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }
        $args = $node->getArgs();
        if (count($args) !== 2) {
            return null;
        }
        $classReflection = $scope->getClassReflection();
        $isTrait = $classReflection instanceof ClassReflection && $classReflection->isTrait();
        $functionReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
        if (!$functionReflection instanceof FunctionReflection) {
            return null;
        }
        $argPosition = $this->argsAnalyzer->resolveArgPosition($args, 'key', 0);
        $originalType = $this->getType($args[$argPosition]->value);
        if ($originalType instanceof UnionType) {
            $withoutNullParameterType = TypeCombinator::removeNull($originalType);
            if ($withoutNullParameterType->equals($originalType)) {
                return null;
            }
        }
        $parametersAcceptor = ParametersAcceptorSelectorVariantsWrapper::select($functionReflection, $node, $scope);
        $result = $this->nullToStrictStringIntConverter->convertIfNull($node, $args, $argPosition, $isTrait, $scope, $parametersAcceptor);
        if ($result instanceof Node) {
            return $result;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATE_NULL_ARG_IN_ARRAY_KEY_EXISTS_FUNCTION;
    }
}
