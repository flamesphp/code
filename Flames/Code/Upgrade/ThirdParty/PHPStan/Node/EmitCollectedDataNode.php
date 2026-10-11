<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Collectors\Collector;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @template TNodeType of Node
 * @template TValue
 */
#[ReferencedByTurboExtension('emitCollectedDataNode')]
final class EmitCollectedDataNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var class-string<Collector<TNodeType, TValue>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public string $collectorType;
    /**
     * @var TValue
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public $data;
    /**
     * @param class-string<Collector<TNodeType, TValue>> $collectorType
     * @param TValue $data
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        string $collectorType,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        $data
    )
    {
        $this->collectorType = $collectorType;
        $this->data = $data;
        parent::__construct([]);
    }
    /**
     * @return class-string<Collector<TNodeType, TValue>>
     */
    public function getCollectorType(): string
    {
        return $this->collectorType;
    }
    /**
     * @return TValue
     */
    public function getData()
    {
        return $this->data;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_EmitCollectedDataNode';
    }
    /**
     * @return list<string>
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
}
