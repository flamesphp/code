<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VoidType;
use function in_array;
use function sprintf;
use function strtolower;
/**
 * @api
 */
#[ReferencedByTurboExtension('phpMethodFromParserNodeReflection')]
final class PhpMethodFromParserNodeReflection extends \PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection implements ExtendedMethodReflection
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $declaringClass;
    /**
     * @var \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\PropertyHook
     */
    private $classMethod;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $hookForProperty;
    private bool $isFinal;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $selfOutType;
    private ?ResolvedPhpDocBlock $resolvedPhpDoc;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isConstructor;
    /**
     * @param Type[] $realParameterTypes
     * @param Type[] $phpDocParameterTypes
     * @param Type[] $realParameterDefaultValues
     * @param array<string, list<AttributeReflection>> $parameterAttributes
     * @param array<string, bool> $immediatelyInvokedCallableParameters
     * @param array<string, Type> $phpDocClosureThisTypeParameters
     * @param list<AttributeReflection> $attributes
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\PropertyHook $classMethod
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $declaringClass,
        $classMethod,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $hookForProperty,
        string $fileName,
        TemplateTypeMap $templateTypeMap,
        array $realParameterTypes,
        array $phpDocParameterTypes,
        array $realParameterDefaultValues,
        array $parameterAttributes,
        Type $realReturnType,
        ?Type $phpDocReturnType,
        ?Type $throwType,
        ?string $deprecatedDescription,
        bool $isDeprecated,
        bool $isInternal,
        bool $isFinal,
        ?bool $isPure,
        bool $acceptsNamedArguments,
        Assertions $assertions,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $selfOutType,
        ?string $phpDocComment,
        ?ResolvedPhpDocBlock $resolvedPhpDoc,
        array $parameterOutTypes,
        array $immediatelyInvokedCallableParameters,
        array $phpDocClosureThisTypeParameters,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isConstructor,
        array $attributes,
        array $pureUnlessCallableIsImpureParameters,
        array $pureUnlessParameterPassedParameters
    )
    {
        $this->declaringClass = $declaringClass;
        $this->classMethod = $classMethod;
        $this->hookForProperty = $hookForProperty;
        $this->isFinal = $isFinal;
        $this->selfOutType = $selfOutType;
        $this->resolvedPhpDoc = $resolvedPhpDoc;
        $this->isConstructor = $isConstructor;
        if ($this->classMethod instanceof Node\PropertyHook) {
            if ($this->hookForProperty === null) {
                throw new ShouldNotHappenException('Hook was provided but property was not');
            }
        } elseif ($this->hookForProperty !== null) {
            throw new ShouldNotHappenException('Hooked property was provided but hook was not');
        }
        $name = strtolower($classMethod->name->name);
        if ($this->isConstructor) {
            $realReturnType = new VoidType();
        }
        if (in_array($name, ['__destruct', '__unset', '__wakeup', '__clone'], \true)) {
            $realReturnType = new VoidType();
        }
        if ($name === '__tostring') {
            $realReturnType = new StringType();
        }
        if ($name === '__isset') {
            $realReturnType = new BooleanType();
        }
        if ($name === '__sleep') {
            $realReturnType = new ArrayType(new IntegerType(), new StringType());
        }
        if ($name === '__set_state') {
            $realReturnType = TypeCombinator::intersect(new ObjectWithoutClassType(), $realReturnType);
        }
        if ($name === '__set') {
            $realReturnType = new VoidType();
        }
        if ($name === '__debuginfo') {
            $realReturnType = TypeCombinator::intersect(new UnionType([new ArrayType(new MixedType(\true), new MixedType(\true)), new NullType()]), $realReturnType);
        }
        if ($name === '__unserialize') {
            $realReturnType = new VoidType();
        }
        if ($name === '__serialize') {
            $realReturnType = new ArrayType(new MixedType(\true), new MixedType(\true));
        }
        parent::__construct($classMethod, $fileName, $templateTypeMap, $realParameterTypes, $phpDocParameterTypes, $realParameterDefaultValues, $parameterAttributes, $realReturnType, $phpDocReturnType, $throwType, $deprecatedDescription, $isDeprecated, $isInternal, $isPure, $acceptsNamedArguments, $assertions, $phpDocComment, $parameterOutTypes, $immediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $attributes, $pureUnlessCallableIsImpureParameters, $pureUnlessParameterPassedParameters);
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }
    public function getPrototype(): ClassMemberReflection
    {
        try {
            return $this->declaringClass->getNativeMethod($this->getClassMethod()->name->name)->getPrototype();
        } catch (MissingMethodFromReflectionException $e) {
            return $this;
        }
    }
    /**
     * @return \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\PropertyHook
     */
    private function getClassMethod()
    {
        /** @var Node\Stmt\ClassMethod|Node\PropertyHook $functionLike */
        $functionLike = $this->getFunctionLike();
        return $functionLike;
    }
    public function getName(): string
    {
        $function = $this->getFunctionLike();
        if (!$function instanceof Node\PropertyHook) {
            return parent::getName();
        }
        if ($this->hookForProperty === null) {
            throw new ShouldNotHappenException('Hook was provided but property was not');
        }
        return sprintf('$%s::%s', $this->hookForProperty, $function->name->name);
    }
    /**
     * @phpstan-assert-if-true !null $this->getHookedPropertyName()
     * @phpstan-assert-if-true !null $this->getPropertyHookName()
     */
    public function isPropertyHook(): bool
    {
        return $this->hookForProperty !== null;
    }
    public function getHookedPropertyName(): ?string
    {
        return $this->hookForProperty;
    }
    /**
     * @return 'get'|'set'|null
     */
    public function getPropertyHookName(): ?string
    {
        $function = $this->getFunctionLike();
        if (!$function instanceof Node\PropertyHook) {
            return null;
        }
        $name = strtolower($function->name->name);
        if (!in_array($name, ['get', 'set'], \true)) {
            throw new ShouldNotHappenException(sprintf('Unknown property hook: %s', $name));
        }
        return $name;
    }
    public function isStatic(): bool
    {
        $method = $this->getClassMethod();
        if ($method instanceof Node\PropertyHook) {
            return \false;
        }
        return (bool) ($method->flags & \PhpParser\Modifiers::STATIC);
    }
    public function isPrivate(): bool
    {
        $method = $this->getClassMethod();
        if ($method instanceof Node\PropertyHook) {
            return \false;
        }
        return (bool) ($method->flags & \PhpParser\Modifiers::PRIVATE);
    }
    public function isPublic(): bool
    {
        $method = $this->getClassMethod();
        if ($method instanceof Node\PropertyHook) {
            return \true;
        }
        return ($method->flags & \PhpParser\Modifiers::PUBLIC) !== 0 || ($method->flags & \PhpParser\Modifiers::VISIBILITY_MASK) === 0;
    }
    public function isFinal(): TrinaryLogic
    {
        $method = $this->getClassMethod();
        if ($method instanceof Node\PropertyHook) {
            return TrinaryLogic::createFromBoolean((bool) ($method->flags & \PhpParser\Modifiers::FINAL));
        }
        return TrinaryLogic::createFromBoolean((bool) ($method->flags & \PhpParser\Modifiers::FINAL) || $this->isFinal);
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->getClassMethod()->isFinal());
    }
    public function isBuiltin(): bool
    {
        return \false;
    }
    public function getSelfOutType(): ?Type
    {
        return $this->selfOutType;
    }
    public function returnsByReference(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->getClassMethod()->returnsByRef());
    }
    public function isAbstract(): TrinaryLogic
    {
        $method = $this->getClassMethod();
        if ($method instanceof Node\PropertyHook) {
            return TrinaryLogic::createFromBoolean($method->body === null);
        }
        return TrinaryLogic::createFromBoolean((bool) ($method->flags & \PhpParser\Modifiers::ABSTRACT));
    }
    public function isConstructor(): bool
    {
        return $this->isConstructor;
    }
    public function hasSideEffects(): TrinaryLogic
    {
        if (strtolower($this->getName()) !== '__construct' && $this->getReturnType()->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ($this->isPure !== null) {
            return TrinaryLogic::createFromBoolean(!$this->isPure);
        }
        return TrinaryLogic::createMaybe();
    }
    public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
    {
        return $this->resolvedPhpDoc;
    }
}
