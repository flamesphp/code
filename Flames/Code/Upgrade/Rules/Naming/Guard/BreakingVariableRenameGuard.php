<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Guard;

use DateTimeInterface;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Error;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rules\Naming\Naming\ConflictingNameResolver;
use Flames\Code\Upgrade\Rules\Naming\Naming\OverriddenExistingNamesResolver;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Utils\TypeUnwrapper;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
use Flames\Code\Upgrade\Util\StringUtils;
/**
 * This class check if a variable name change breaks existing code in class method
 */
final readonly class BreakingVariableRenameGuard
{
    /**
     * @see https://regex101.com/r/1pKLgf/1
     */
    public const string AT_NAMING_REGEX = '#[\w+]At$#';
    public function __construct(private BetterNodeFinder $betterNodeFinder, private ConflictingNameResolver $conflictingNameResolver, private NodeTypeResolver $nodeTypeResolver, private OverriddenExistingNamesResolver $overriddenExistingNamesResolver, private TypeUnwrapper $typeUnwrapper, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    public function shouldSkipVariable(string $currentName, string $expectedName, $functionLike, Variable $variable): bool
    {
        // is the suffix? → also accepted
        $expectedNameCamelCase = ucfirst($expectedName);
        if (str_ends_with($currentName, $expectedNameCamelCase)) {
            return \true;
        }
        if ($this->conflictingNameResolver->hasNameIsInFunctionLike($expectedName, $functionLike)) {
            return \true;
        }
        if (!$functionLike instanceof ArrowFunction && $this->overriddenExistingNamesResolver->hasNameInClassMethodForNew($currentName, $functionLike)) {
            return \true;
        }
        if ($this->isVariableAlreadyDefined($variable, $currentName)) {
            return \true;
        }
        if ($this->hasConflictVariable($functionLike, $expectedName)) {
            return \true;
        }
        return $functionLike instanceof Closure && $this->isUsedInClosureUsesName($expectedName, $functionLike);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $classMethod
     */
    public function shouldSkipParam(string $currentName, string $expectedName, $classMethod, Param $param): bool
    {
        // is the suffix? → also accepted
        $expectedNameCamelCase = ucfirst($expectedName);
        if (str_ends_with($currentName, $expectedNameCamelCase)) {
            return \true;
        }
        $conflictingNames = $this->conflictingNameResolver->resolveConflictingVariableNamesForParam($classMethod);
        if (in_array($expectedName, $conflictingNames, \true)) {
            return \true;
        }
        if ($this->conflictingNameResolver->hasNameIsInFunctionLike($expectedName, $classMethod)) {
            return \true;
        }
        if ($this->overriddenExistingNamesResolver->hasNameInFunctionLikeForParam($expectedName, $classMethod)) {
            return \true;
        }
        if ($param->var instanceof Error) {
            return \true;
        }
        if ($this->isVariableAlreadyDefined($param->var, $currentName)) {
            return \true;
        }
        if ($this->isRamseyUuidInterface($param)) {
            return \true;
        }
        if ($this->isGenerator($param)) {
            return \true;
        }
        if ($this->isDateTimeAtNamingConvention($param)) {
            return \true;
        }
        return (bool) $this->betterNodeFinder->findFirst((array) $classMethod->getStmts(), function (Node $node) use ($expectedName): bool {
            if (!$node instanceof Variable) {
                return \false;
            }
            return $this->nodeNameResolver->isName($node, $expectedName);
        });
    }
    private function isVariableAlreadyDefined(Variable $variable, string $currentVariableName): bool
    {
        $scope = $variable->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return \false;
        }
        $trinaryLogic = $scope->hasVariableType($currentVariableName);
        if ($trinaryLogic->yes()) {
            return \true;
        }
        return $trinaryLogic->maybe();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    private function hasConflictVariable($functionLike, string $newName): bool
    {
        if ($functionLike instanceof ArrowFunction) {
            return $this->betterNodeFinder->hasInstanceOfName(array_merge([$functionLike->expr], $functionLike->params), Variable::class, $newName);
        }
        return $this->betterNodeFinder->hasInstanceOfName(array_merge((array) $functionLike->stmts, $functionLike->params), Variable::class, $newName);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    private function isUsedInClosureUsesName(string $expectedName, $functionLike): bool
    {
        if (!$functionLike instanceof Closure) {
            return \false;
        }
        return $this->betterNodeFinder->hasVariableOfName($functionLike->uses, $expectedName);
    }
    private function isRamseyUuidInterface(Param $param): bool
    {
        return $this->nodeTypeResolver->isObjectType($param, new ObjectType('Ramsey\Uuid\UuidInterface'));
    }
    private function isDateTimeAtNamingConvention(Param $param): bool
    {
        $type = $this->nodeTypeResolver->getType($param);
        $type = $this->typeUnwrapper->unwrapFirstObjectTypeFromUnionType($type);
        $className = ClassNameFromObjectTypeResolver::resolve($type);
        if ($className === null) {
            return \false;
        }
        if (!is_a($className, DateTimeInterface::class, \true)) {
            return \false;
        }
        /** @var string $currentName */
        $currentName = $this->nodeNameResolver->getName($param);
        return StringUtils::isMatch($currentName, self::AT_NAMING_REGEX);
    }
    private function isGenerator(Param $param): bool
    {
        if (!$param->type instanceof Node) {
            return \false;
        }
        $paramType = $this->nodeTypeResolver->getType($param);
        if (!$paramType instanceof ObjectType) {
            return \false;
        }
        if (str_ends_with($paramType->getClassName(), 'Generator') || str_ends_with($paramType->getClassName(), 'Iterator')) {
            return \true;
        }
        return $paramType->isInstanceOf('Symfony\Component\DependencyInjection\Argument\RewindableGenerator')->yes();
    }
}
