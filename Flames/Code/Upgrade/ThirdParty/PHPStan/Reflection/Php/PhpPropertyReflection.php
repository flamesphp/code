<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function sprintf;
/**
 * @api
 */
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/PhpPropertyReflection.cpp')]
final class PhpPropertyReflection implements ExtendedPropertyReflection
{
    private ClassReflection $declaringClass;
    private ?ClassReflection $declaringTrait;
    private Type $nativeType;
    private ?Type $readablePhpDocType;
    private ?Type $writablePhpDocType;
    private ReflectionProperty $reflection;
    private ?ExtendedMethodReflection $getHook;
    private ?ExtendedMethodReflection $setHook;
    private ?ResolvedPhpDocBlock $resolvedPhpDocBlock;
    private ?string $deprecatedDescription;
    private bool $isDeprecated;
    private bool $isInternal;
    private bool $isReadOnlyByPhpDoc;
    private bool $isAllowedPrivateMutation;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private bool $isFinal;
    private bool $readable;
    private bool $writable;
    private bool $private;
    private bool $public;
    private ?Type $readableType = null;
    private ?Type $writableType = null;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(ClassReflection $declaringClass, ?ClassReflection $declaringTrait, Type $nativeType, ?Type $readablePhpDocType, ?Type $writablePhpDocType, ReflectionProperty $reflection, ?ExtendedMethodReflection $getHook, ?ExtendedMethodReflection $setHook, ?ResolvedPhpDocBlock $resolvedPhpDocBlock, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, bool $isReadOnlyByPhpDoc, bool $isAllowedPrivateMutation, array $attributes, bool $isFinal, bool $readable, bool $writable, bool $private, bool $public)
    {
        $this->declaringClass = $declaringClass;
        $this->declaringTrait = $declaringTrait;
        $this->nativeType = $nativeType;
        $this->readablePhpDocType = $readablePhpDocType;
        $this->writablePhpDocType = $writablePhpDocType;
        $this->reflection = $reflection;
        $this->getHook = $getHook;
        $this->setHook = $setHook;
        $this->resolvedPhpDocBlock = $resolvedPhpDocBlock;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->isReadOnlyByPhpDoc = $isReadOnlyByPhpDoc;
        $this->isAllowedPrivateMutation = $isAllowedPrivateMutation;
        $this->attributes = $attributes;
        $this->isFinal = $isFinal;
        $this->readable = $readable;
        $this->writable = $writable;
        $this->private = $private;
        $this->public = $public;
    }
    public function getName(): string
    {
        return $this->reflection->betterReflectionProperty->getName();
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }
    public function getDeclaringTrait(): ?ClassReflection
    {
        return $this->declaringTrait;
    }
    public function getDocComment(): ?string
    {
        $docComment = $this->reflection->betterReflectionProperty->getDocComment() ?? \false;
        if ($docComment === \false) {
            return null;
        }
        return $docComment;
    }
    public function isStatic(): bool
    {
        return $this->reflection->betterReflectionProperty->isStatic();
    }
    public function isPrivate(): bool
    {
        return $this->private;
    }
    public function isPublic(): bool
    {
        return $this->public;
    }
    public function isReadOnly(): bool
    {
        return $this->reflection->betterReflectionProperty->isReadOnly();
    }
    public function isReadOnlyByPhpDoc(): bool
    {
        return $this->isReadOnlyByPhpDoc;
    }
    public function getReadableType(): Type
    {
        return $this->readableType ??= TypehintHelper::decideType($this->nativeType, $this->readablePhpDocType);
    }
    public function getWritableType(): Type
    {
        if ($this->hasHook('set')) {
            $setHookVariant = $this->getHook('set')->getOnlyVariant();
            $parameters = $setHookVariant->getParameters();
            if (isset($parameters[0])) {
                return $parameters[0]->getType();
            }
        }
        if ($this->writableType !== null) {
            return $this->writableType;
        }
        if ($this->writablePhpDocType === null || $this->writablePhpDocType instanceof NeverType) {
            return $this->writableType = TypehintHelper::decideType($this->nativeType, $this->readablePhpDocType);
        }
        if ($this->readablePhpDocType !== null && !$this->readablePhpDocType->equals($this->writablePhpDocType)) {
            return $this->writableType = $this->writablePhpDocType;
        }
        return $this->writableType = TypehintHelper::decideType($this->nativeType, $this->writablePhpDocType);
    }
    public function canChangeTypeAfterAssignment(): bool
    {
        if ($this->isStatic()) {
            return $this->getReadableType()->equals($this->getWritableType());
        }
        if ($this->isVirtual()->yes()) {
            return \false;
        }
        if ($this->hasHook('get')) {
            return \false;
        }
        if ($this->hasHook('set')) {
            return \false;
        }
        return $this->getReadableType()->equals($this->getWritableType());
    }
    public function isPromoted(): bool
    {
        return $this->reflection->betterReflectionProperty->isPromoted();
    }
    public function hasPhpDocType(): bool
    {
        return $this->readablePhpDocType !== null;
    }
    public function getPhpDocType(): Type
    {
        if ($this->readablePhpDocType !== null) {
            return $this->readablePhpDocType;
        }
        return new MixedType();
    }
    public function hasNativeType(): bool
    {
        return !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
    }
    public function getNativeType(): Type
    {
        return $this->nativeType;
    }
    public function isReadable(): bool
    {
        if (!$this->readable) {
            return \false;
        }
        if ($this->isStatic()) {
            return \true;
        }
        if (!$this->isVirtual()->yes()) {
            return \true;
        }
        return $this->hasHook('get');
    }
    public function isWritable(): bool
    {
        if (!$this->writable) {
            return \false;
        }
        if ($this->isStatic()) {
            return \true;
        }
        if (!$this->isVirtual()->yes()) {
            return \true;
        }
        return $this->hasHook('set');
    }
    public function getDeprecatedDescription(): ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        return null;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function isAllowedPrivateMutation(): bool
    {
        return $this->isAllowedPrivateMutation;
    }
    public function getNativeReflection(): ReflectionProperty
    {
        return $this->reflection;
    }
    public function isAbstract(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionProperty->isAbstract());
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionProperty->isFinal());
    }
    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isFinal);
    }
    public function isVirtual(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->betterReflectionProperty->isVirtual());
    }
    public function hasHook(string $hookType): bool
    {
        if ($hookType === 'get') {
            return $this->getHook !== null;
        }
        return $this->setHook !== null;
    }
    public function isHooked(): bool
    {
        return $this->getHook !== null || $this->setHook !== null;
    }
    public function getHook(string $hookType): ExtendedMethodReflection
    {
        if ($hookType === 'get') {
            if ($this->getHook === null) {
                throw new MissingMethodFromReflectionException($this->declaringClass->getName(), sprintf('$%s::get', $this->reflection->betterReflectionProperty->getName()));
            }
            return $this->getHook;
        }
        if ($this->setHook === null) {
            throw new MissingMethodFromReflectionException($this->declaringClass->getName(), sprintf('$%s::set', $this->reflection->betterReflectionProperty->getName()));
        }
        return $this->setHook;
    }
    public function isProtectedSet(): bool
    {
        return $this->reflection->betterReflectionProperty->isProtectedSet();
    }
    public function isPrivateSet(): bool
    {
        return $this->reflection->betterReflectionProperty->isPrivateSet();
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    public function isDummy(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
    {
        return $this->resolvedPhpDocBlock;
    }
}
