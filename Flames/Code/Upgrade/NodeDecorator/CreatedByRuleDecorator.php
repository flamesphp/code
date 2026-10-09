<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeDecorator;

use PhpParser\Node;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
final class CreatedByRuleDecorator
{
    /**
     * @param array<Node>|Node $node
     * @param class-string<RectorInterface> $rectorClass
     */
    public function decorate($node, Node $originalNode, string $rectorClass): void
    {
        if ($node instanceof Node && $node === $originalNode) {
            $this->createByRule($node, $rectorClass);
            return;
        }
        if ($node instanceof Node) {
            $node = [$node];
        }
        foreach ($node as $singleNode) {
            if ($singleNode::class === $originalNode::class) {
                $this->createByRule($singleNode, $rectorClass);
            }
        }
        $this->createByRule($originalNode, $rectorClass);
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    private function createByRule(Node $node, string $rectorClass): void
    {
        /** @var class-string<RectorInterface>[] $createdByRule */
        $createdByRule = $node->getAttribute(AttributeKey::CREATED_BY_RULE) ?? [];
        // empty array, insert
        if ($createdByRule === []) {
            $node->setAttribute(AttributeKey::CREATED_BY_RULE, [$rectorClass]);
            return;
        }
        // consecutive, no need to refill
        if (end($createdByRule) === $rectorClass) {
            return;
        }
        // filter out when exists, then append
        $createdByRule = array_filter($createdByRule, static fn(string $rectorRule): bool => $rectorRule !== $rectorClass);
        $node->setAttribute(AttributeKey::CREATED_BY_RULE, array_merge($createdByRule, [$rectorClass]));
    }
}
