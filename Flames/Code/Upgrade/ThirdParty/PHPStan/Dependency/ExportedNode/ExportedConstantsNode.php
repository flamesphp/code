<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\ExportedNodeDecoder;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedConstantsNode implements RootExportedNode, JsonSerializable
{
    /**
     * @var ExportedConstantNode[]
     */
    private array $constants;
    /**
     * @param ExportedConstantNode[] $constants
     */
    public function __construct(array $constants)
    {
        $this->constants = $constants;
    }
    public function getType(): string
    {
        return self::TYPE_CONSTANT;
    }
    public function getName(): string
    {
        return $this->constants[0]->name;
    }
    public function equals(ExportedNode $node): bool
    {
        if (!$node instanceof self) {
            return \false;
        }
        if (count($this->constants) !== count($node->constants)) {
            return \false;
        }
        foreach ($this->constants as $i => $constant) {
            if (!$constant->equals($node->constants[$i])) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['constants']);
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data, ExportedNodeDecoder $decoder): self
    {
        return new self(array_map(static function (array $constantData) use ($decoder): \PHPStan\Dependency\ExportedNode\ExportedConstantNode {
            if ($constantData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedConstantNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedConstantNode::decode($constantData['data'], $decoder);
        }, $data['constants']));
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    #[Override]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['constants' => $this->constants]];
    }
}
