<?php

declare (strict_types=1);
namespace PHPStan\Node\Variable;

use PhpParser\Node;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * A write site of a local variable inside a function-like body.
 *
 * Immutable. Whether the written value was read afterwards is not a property
 * of the write - it is answered by VariableWritesNode::isRead().
 *
 * @api
 */
#[ReferencedByTurboExtension('variableWrite')]
final class VariableWrite
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $variableName;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node $node;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $id;
    /**
     * @var self::KIND_*
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $kind;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $offsetWrite;
    /**
     * @var int|string|null
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $offset;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?int $parentId;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $replacesOffset;
    public const KIND_ASSIGN = 1;
    public const KIND_READ_MODIFY_WRITE = 2;
    public const KIND_PRE_INC = 3;
    public const KIND_POST_INC = 4;
    public const KIND_PRE_DEC = 5;
    public const KIND_POST_DEC = 6;
    public const KIND_ARRAY_DIM_WRITE = 7;
    public const KIND_LIST_ITEM = 8;
    public const KIND_FOREACH_VALUE = 9;
    public const KIND_FOREACH_KEY = 10;
    public const KIND_CATCH = 11;
    public const KIND_PARAMETER = 12;
    public const KIND_CLOSURE_USE = 13;
    public const KIND_ARRAY_LITERAL_ITEM = 14;
    public const KIND_FOREACH_VALUE_WITH_KEY = 15;
    /**
     * @param self::KIND_* $kind
     * @param int|string|null $offset
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $variableName,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node $node,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $id,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $kind,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $offsetWrite = \false,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $offset = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?int $parentId = null,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $replacesOffset = \true
    )
    {
        $this->variableName = $variableName;
        $this->node = $node;
        $this->id = $id;
        $this->kind = $kind;
        $this->offsetWrite = $offsetWrite;
        $this->offset = $offset;
        $this->parentId = $parentId;
        $this->replacesOffset = $replacesOffset;
    }
    public function getVariableName(): string
    {
        return $this->variableName;
    }
    /**
     * The target node of the write - the source of the reported line.
     */
    public function getNode(): Node
    {
        return $this->node;
    }
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * @return self::KIND_*
     */
    public function getKind(): int
    {
        return $this->kind;
    }
    public function isOffsetWrite(): bool
    {
        return $this->offsetWrite;
    }
    /** @return int|string|null */
    public function getOffset()
    {
        return $this->offset;
    }
    public function getParentId(): ?int
    {
        return $this->parentId;
    }
    public function replacesOffset(): bool
    {
        return $this->replacesOffset;
    }
}
