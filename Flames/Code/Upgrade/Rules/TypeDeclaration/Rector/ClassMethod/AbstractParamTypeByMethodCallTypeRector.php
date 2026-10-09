<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\Mapper\PhpParserNodeMapper;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Guard\ParamTypeAddGuard;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\CallerParamMatcher;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
/**
 * Shared engine for adding a param type based on the type of the caller argument.
 * Concrete rules narrow the change to a single type group via isMatchingParamType().
 */
abstract class AbstractParamTypeByMethodCallTypeUpgrade extends AbstractRector
{
    public function __construct(private readonly CallerParamMatcher $callerParamMatcher, private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly ParamTypeAddGuard $paramTypeAddGuard, private readonly BetterNodeFinder $betterNodeFinder, private readonly PhpParserNodeMapper $phpParserNodeMapper, private readonly StaticTypeMapper $staticTypeMapper, private readonly TypeFactory $typeFactory)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class];
    }
    /**
     * @param ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->params === []) {
            return null;
        }
        // has params with at least one missing type
        if (!$this->hasAtLeastOneParamWithoutType($node)) {
            return null;
        }
        if ($node instanceof ClassMethod && $this->shouldSkipClassMethod($node)) {
            return null;
        }
        /** @var array<StaticCall|MethodCall|FuncCall> $callers */
        $callers = $this->betterNodeFinder->findInstancesOfScoped([$node], [StaticCall::class, MethodCall::class, FuncCall::class]);
        // keep only callers with args
        $callersWithArgs = array_filter($callers, fn($caller): bool => $caller->args !== []);
        if ($callersWithArgs === []) {
            return null;
        }
        $hasChanged = $this->refactorFunctionLike($node, $callersWithArgs);
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * Decide whether the inferred param type belongs to this rule's type group.
     */
    abstract protected function isMatchingParamType(Type $type): bool;
    private function shouldSkipClassMethod(ClassMethod $classMethod): bool
    {
        // trait method can be used in a class that requires a contract type, adding a param type would break it
        $scope = $classMethod->getAttribute(AttributeKey::SCOPE);
        if ($scope instanceof Scope && $scope->isInTrait()) {
            return \true;
        }
        // user-guarded class: adding a param type here would break its child classes
        if ($this->parentClassMethodTypeOverrideGuard->isTypeGuardedClass($classMethod)) {
            return \true;
        }
        $isMissingParameterTypes = \false;
        foreach ($classMethod->params as $param) {
            if ($param->type instanceof Node) {
                continue;
            }
            if ($param->variadic) {
                continue;
            }
            $isMissingParameterTypes = \true;
        }
        if ($isMissingParameterTypes === \false) {
            return \true;
        }
        return $this->parentClassMethodTypeOverrideGuard->hasParentClassMethod($classMethod);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    private function shouldSkipParam(Param $param, $functionLike): bool
    {
        // already has type, skip
        if ($param->type instanceof Node) {
            return \true;
        }
        if ($param->variadic) {
            return \true;
        }
        return !$this->paramTypeAddGuard->isLegal($param, $functionLike);
    }
    /**
     * @param array<StaticCall|MethodCall|FuncCall> $callers
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    private function refactorFunctionLike($functionLike, array $callers): bool
    {
        $hasChanged = \false;
        foreach ($functionLike->params as $param) {
            if ($this->shouldSkipParam($param, $functionLike)) {
                continue;
            }
            $paramTypes = [];
            foreach ($callers as $caller) {
                $matchCallParam = $this->callerParamMatcher->matchCallParam($caller, $param);
                // nothing to do with param, continue
                if (!$matchCallParam instanceof Param) {
                    continue;
                }
                $paramType = $this->callerParamMatcher->matchCallParamType($param, $matchCallParam);
                if (!$paramType instanceof Node) {
                    $paramTypes = [];
                    break;
                }
                if ($caller->getAttribute(AttributeKey::IS_RIGHT_AND)) {
                    $paramTypes = [];
                    break;
                }
                $paramTypes[] = $this->phpParserNodeMapper->mapToPHPStanType($paramType);
            }
            if ($paramTypes === []) {
                continue;
            }
            $type = $this->typeFactory->createMixedPassedOrUnionType($paramTypes);
            // only handle the type group owned by this rule
            if (!$this->isMatchingParamType($type)) {
                continue;
            }
            $paramNodeType = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($type, TypeKind::PARAM);
            if ($paramNodeType instanceof Node) {
                $param->type = $paramNodeType;
                $hasChanged = \true;
            }
        }
        return $hasChanged;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrowFunction $functionLike
     */
    private function hasAtLeastOneParamWithoutType($functionLike): bool
    {
        $found = \false;
        foreach ($functionLike->params as $param) {
            if (!$param->type instanceof Node) {
                $found = \true;
                break;
            }
        }
        return $found;
    }
}
