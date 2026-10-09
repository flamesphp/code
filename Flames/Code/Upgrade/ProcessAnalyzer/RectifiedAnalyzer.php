<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ProcessAnalyzer;

use PhpParser\Node;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\NodeAnalyzer\ScopeAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
/**
 * This service verify if the Node:
 *
 *      - already applied same Upgrade rule before current Upgrade rule on last previous Upgrade rule.
 *      - just re-printed but token start still >= 0
 */
final readonly class RectifiedAnalyzer
{
    public function __construct(private ScopeAnalyzer $scopeAnalyzer)
    {
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    public function hasRectified(string $rectorClass, Node $node): bool
    {
        $originalNode = $node->getAttribute(AttributeKey::ORIGINAL_NODE);
        if ($this->hasConsecutiveCreatedByRule($rectorClass, $node, $originalNode)) {
            return \true;
        }
        return $this->isJustReprintedOverlappedTokenStart($node, $originalNode);
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    private function hasConsecutiveCreatedByRule(string $rectorClass, Node $node, ?Node $originalNode): bool
    {
        $createdByRuleNode = $originalNode ?? $node;
        /** @var class-string<RectorInterface>[] $createdByRule */
        $createdByRule = $createdByRuleNode->getAttribute(AttributeKey::CREATED_BY_RULE) ?? [];
        if ($createdByRule === []) {
            return \false;
        }
        return end($createdByRule) === $rectorClass;
    }
    private function isJustReprintedOverlappedTokenStart(Node $node, ?Node $originalNode): bool
    {
        if ($originalNode instanceof Node) {
            return \false;
        }
        /**
         * Start token pos must be < 0 to continue, as the node and parent node just re-printed
         *
         * - Node's original node is null
         * - Parent Node's original node is null
         */
        $startTokenPos = $node->getStartTokenPos();
        if ($startTokenPos >= 0) {
            return \true;
        }
        if (!$this->scopeAnalyzer->isRefreshable($node)) {
            return \false;
        }
        return !in_array(AttributeKey::SCOPE, array_keys($node->getAttributes()), \true);
    }
}
