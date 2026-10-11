<?php

declare (strict_types=1);
namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
#[RegisteredRule(0)]
final class ConflictingTraitConstantsRule implements Rule
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionProvider $reflectionProvider;
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionProvider $reflectionProvider)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\ClassConst::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }
        $classReflection = $scope->getClassReflection();
        $recursiveTraitConstants = [];
        foreach ($classReflection->getTraits(\true) as $trait) {
            foreach ($trait->getNativeReflection()->getReflectionConstants() as $constant) {
                $recursiveTraitConstants[] = $constant;
            }
        }
        $errors = [];
        foreach ($node->consts as $const) {
            foreach ($recursiveTraitConstants as $recursiveTraitConstant) {
                if ($recursiveTraitConstant->betterClassConstantOrEnumCase->getName() !== $const->name->name) {
                    continue;
                }
                foreach ($this->processSingleConstant($classReflection, $recursiveTraitConstant, $node) as $error) {
                    $errors[] = $error;
                }
            }
        }
        $immediateTraitConstants = [];
        foreach ($classReflection->getTraits() as $trait) {
            foreach ($trait->getNativeReflection()->getReflectionConstants() as $constant) {
                $immediateTraitConstants[] = $constant;
            }
        }
        foreach ($node->consts as $const) {
            foreach ($immediateTraitConstants as $immediateTraitConstant) {
                if ($immediateTraitConstant->betterClassConstantOrEnumCase->getName() !== $const->name->name) {
                    continue;
                }
                $classConstantValueType = $this->initializerExprTypeResolver->getType($const->value, InitializerExprContext::fromClassReflection($classReflection));
                $traitConstantValueType = $this->initializerExprTypeResolver->getType($immediateTraitConstant->betterClassConstantOrEnumCase->getValueExpression(), InitializerExprContext::fromClassReflection($classReflection));
                if ($classConstantValueType->equals($traitConstantValueType)) {
                    continue;
                }
                $errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s with value %s overriding constant %s::%s with different value %s should have the same value.', $classReflection->getDisplayName(), $immediateTraitConstant->betterClassConstantOrEnumCase->getName(), $classConstantValueType->describe(VerbosityLevel::value()), $immediateTraitConstant->getDeclaringClass()->betterReflectionClass->getName(), $immediateTraitConstant->betterClassConstantOrEnumCase->getName(), $traitConstantValueType->describe(VerbosityLevel::value())))->nonIgnorable()->identifier('classConstant.value')->build();
            }
        }
        return $errors;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    private function processSingleConstant(ClassReflection $classReflection, ReflectionClassConstant $traitConstant, Node\Stmt\ClassConst $classConst): array
    {
        $errors = [];
        if ($traitConstant->isPublic()) {
            if ((bool) ($classConst->flags & \PhpParser\Modifiers::PROTECTED)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Protected constant %s::%s overriding public constant %s::%s should also be public.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.visibility')->build();
            } elseif ((bool) ($classConst->flags & \PhpParser\Modifiers::PRIVATE)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Private constant %s::%s overriding public constant %s::%s should also be public.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.visibility')->build();
            }
        } elseif ($traitConstant->isProtected()) {
            if (($classConst->flags & \PhpParser\Modifiers::PUBLIC) !== 0 || ($classConst->flags & \PhpParser\Modifiers::VISIBILITY_MASK) === 0) {
                $errors[] = RuleErrorBuilder::message(sprintf('Public constant %s::%s overriding protected constant %s::%s should also be protected.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.visibility')->build();
            } elseif ((bool) ($classConst->flags & \PhpParser\Modifiers::PRIVATE)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Private constant %s::%s overriding protected constant %s::%s should also be protected.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.visibility')->build();
            }
        } elseif ($traitConstant->isPrivate()) {
            if (($classConst->flags & \PhpParser\Modifiers::PUBLIC) !== 0 || ($classConst->flags & \PhpParser\Modifiers::VISIBILITY_MASK) === 0) {
                $errors[] = RuleErrorBuilder::message(sprintf('Public constant %s::%s overriding private constant %s::%s should also be private.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.visibility')->build();
            } elseif ((bool) ($classConst->flags & \PhpParser\Modifiers::PROTECTED)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Protected constant %s::%s overriding private constant %s::%s should also be private.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.visibility')->build();
            }
        }
        if ($traitConstant->isFinal()) {
            if (!(bool) ($classConst->flags & \PhpParser\Modifiers::FINAL)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Non-final constant %s::%s overriding final constant %s::%s should also be final.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.nonFinal')->build();
            }
        } elseif ((bool) ($classConst->flags & \PhpParser\Modifiers::FINAL)) {
            $errors[] = RuleErrorBuilder::message(sprintf('Final constant %s::%s overriding non-final constant %s::%s should also be non-final.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.final')->build();
        }
        $traitNativeType = $traitConstant->getType();
        $constantNativeType = $classConst->type;
        $traitDeclaringClass = $traitConstant->getDeclaringClass();
        if ($traitNativeType === null) {
            if ($constantNativeType !== null) {
                $constantNativeTypeType = ParserNodeTypeToPHPStanType::resolve($constantNativeType, $classReflection);
                $errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s (%s) overriding constant %s::%s should not have a native type.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $constantNativeTypeType->describe(VerbosityLevel::typeOnly()), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName()))->nonIgnorable()->identifier('classConstant.nativeType')->build();
            }
        } elseif ($constantNativeType === null) {
            $traitNativeTypeType = TypehintHelper::decideTypeFromReflection($traitNativeType, \null, $this->reflectionProvider->getClass($traitDeclaringClass->betterReflectionClass->getName()));
            $errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s overriding constant %s::%s (%s) should also have native type %s.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitNativeTypeType->describe(VerbosityLevel::typeOnly()), $traitNativeTypeType->describe(VerbosityLevel::typeOnly())))->nonIgnorable()->identifier('classConstant.missingNativeType')->build();
        } else {
            $traitNativeTypeType = TypehintHelper::decideTypeFromReflection($traitNativeType, \null, $this->reflectionProvider->getClass($traitDeclaringClass->betterReflectionClass->getName()));
            $constantNativeTypeType = ParserNodeTypeToPHPStanType::resolve($constantNativeType, $classReflection);
            if (!$traitNativeTypeType->equals($constantNativeTypeType)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s (%s) overriding constant %s::%s (%s) should have the same native type %s.', $classReflection->getDisplayName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $constantNativeTypeType->describe(VerbosityLevel::typeOnly()), $traitConstant->getDeclaringClass()->betterReflectionClass->getName(), $traitConstant->betterClassConstantOrEnumCase->getName(), $traitNativeTypeType->describe(VerbosityLevel::typeOnly()), $traitNativeTypeType->describe(VerbosityLevel::typeOnly())))->nonIgnorable()->identifier('classConstant.nativeType')->build();
            }
        }
        return $errors;
    }
}
