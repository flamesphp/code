<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
/**
 * @api
 */
#[ReferencedByTurboExtension('classPropertyNode')]
final class ClassPropertyNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var non-empty-string
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $name;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $flags;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $type;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Expr $default;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?string $phpDoc;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?Type $phpDocType;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isPromoted;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isPromotedFromTrait;
    /**
     * @var \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $originalNode;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isReadonlyByPhpDoc;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isDeclaredInTrait;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isReadonlyClass;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $isAllowedPrivateMutation;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ClassReflection $classReflection;
    /**
     * @param non-empty-string $name
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $originalNode
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $name,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $flags,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $type,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Expr $default,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?string $phpDoc,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?Type $phpDocType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isPromoted,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isPromotedFromTrait,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $originalNode,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isReadonlyByPhpDoc,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isDeclaredInTrait,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isReadonlyClass,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $isAllowedPrivateMutation,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection
    )
    {
        $this->name = $name;
        $this->flags = $flags;
        $this->type = $type;
        $this->default = $default;
        $this->phpDoc = $phpDoc;
        $this->phpDocType = $phpDocType;
        $this->isPromoted = $isPromoted;
        $this->isPromotedFromTrait = $isPromotedFromTrait;
        $this->originalNode = $originalNode;
        $this->isReadonlyByPhpDoc = $isReadonlyByPhpDoc;
        $this->isDeclaredInTrait = $isDeclaredInTrait;
        $this->isReadonlyClass = $isReadonlyClass;
        $this->isAllowedPrivateMutation = $isAllowedPrivateMutation;
        $this->classReflection = $classReflection;
        parent::__construct($originalNode->getAttributes());
    }
    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }
    public function getFlags(): int
    {
        return $this->flags;
    }
    public function getDefault(): ?Expr
    {
        return $this->default;
    }
    public function isPromoted(): bool
    {
        return $this->isPromoted;
    }
    public function isPromotedFromTrait(): bool
    {
        return $this->isPromotedFromTrait;
    }
    /**
     * @return \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param
     */
    public function getOriginalNode()
    {
        return $this->originalNode;
    }
    public function getPhpDoc(): ?string
    {
        return $this->phpDoc;
    }
    public function getPhpDocType(): ?Type
    {
        return $this->phpDocType;
    }
    public function isPublic(): bool
    {
        return ($this->flags & Modifiers::PUBLIC) !== 0 || ($this->flags & Modifiers::VISIBILITY_MASK) === 0;
    }
    public function isProtected(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED);
    }
    public function isPrivate(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE);
    }
    public function isPrivateSet(): bool
    {
        return (bool) ($this->flags & Modifiers::PRIVATE_SET);
    }
    public function isProtectedSet(): bool
    {
        return (bool) ($this->flags & Modifiers::PROTECTED_SET);
    }
    public function isPublicSet(): bool
    {
        return (bool) ($this->flags & Modifiers::PUBLIC_SET);
    }
    public function isFinal(): bool
    {
        return (bool) ($this->flags & Modifiers::FINAL);
    }
    public function isStatic(): bool
    {
        return (bool) ($this->flags & Modifiers::STATIC);
    }
    public function isReadOnly(): bool
    {
        return (bool) ($this->flags & Modifiers::READONLY) || $this->isReadonlyClass;
    }
    public function isReadOnlyByPhpDoc(): bool
    {
        return $this->isReadonlyByPhpDoc;
    }
    public function isDeclaredInTrait(): bool
    {
        return $this->isDeclaredInTrait;
    }
    public function isAllowedPrivateMutation(): bool
    {
        return $this->isAllowedPrivateMutation;
    }
    public function isAbstract(): bool
    {
        return (bool) ($this->flags & Modifiers::ABSTRACT);
    }
    public function getNativeType(): ?Type
    {
        return $this->type;
    }
    /**
     * @return Node\Identifier|Node\Name|Node\ComplexType|null
     */
    public function getNativeTypeNode()
    {
        return $this->originalNode->type;
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ClassPropertyNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
    public function hasHooks(): bool
    {
        return $this->getHooks() !== [];
    }
    /**
     * @return Node\PropertyHook[]
     */
    public function getHooks(): array
    {
        return $this->originalNode->hooks;
    }
    public function isVirtual(): bool
    {
        return $this->classReflection->getNativeProperty($this->name)->isVirtual()->yes();
    }
    public function isWritable(): bool
    {
        return $this->classReflection->getNativeProperty($this->name)->isWritable();
    }
    public function isReadable(): bool
    {
        return $this->classReflection->getNativeProperty($this->name)->isReadable();
    }
    /**
     * @return Node\AttributeGroup[]
     */
    public function getAttrGroups(): array
    {
        return $this->originalNode->attrGroups;
    }
}
