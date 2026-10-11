<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Lexer\Lexer;
use Flames\Code\Upgrade\BetterPhpDocParser\Attributes\AttributeMirrorer;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\DataProvider\CurrentTokenIteratorProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Parser\BetterTokenIterator;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\StartAndEnd;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor;
final class UnionTypeNodePhpDocNodeVisitor extends AbstractPhpDocNodeVisitor implements BasePhpDocNodeVisitorInterface
{
    public function __construct(private readonly CurrentTokenIteratorProvider $currentTokenIteratorProvider, private readonly AttributeMirrorer $attributeMirrorer)
    {
    }
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof UnionTypeNode) {
            return null;
        }
        if ($node instanceof BracketsAwareUnionTypeNode) {
            return null;
        }
        $startAndEnd = $this->resolveStartAndEnd($node);
        if (!$startAndEnd instanceof StartAndEnd) {
            $firstKey = array_key_first($node->types);
            $lastKey = array_key_last($node->types);
            $startAndEnd = new StartAndEnd($node->types[$firstKey]->getAttribute('startIndex'), $node->types[$lastKey]->getAttribute('endIndex'));
        }
        $betterTokenProvider = $this->currentTokenIteratorProvider->provide();
        $isWrappedInCurlyBrackets = $this->isWrappedInCurlyBrackets($betterTokenProvider, $startAndEnd);
        $bracketsAwareUnionTypeNode = new BracketsAwareUnionTypeNode($node->types, $isWrappedInCurlyBrackets);
        $this->attributeMirrorer->mirror($node, $bracketsAwareUnionTypeNode);
        return $bracketsAwareUnionTypeNode;
    }
    private function isWrappedInCurlyBrackets(BetterTokenIterator $betterTokenProvider, StartAndEnd $startAndEnd): bool
    {
        $previousPosition = $startAndEnd->getStart() - 1;
        $nextPosition = $startAndEnd->getEnd() + 1;
        // A union is wrapped only when BOTH parens flank it. Either alone may be unrelated —
        // e.g. an `@method`'s parameter-list `(` before a first-position union, or the matching
        // `)` after a last-position union — neither belongs to the union type itself.
        return $betterTokenProvider->isTokenTypeOnPosition(Lexer::TOKEN_OPEN_PARENTHESES, $previousPosition) && $betterTokenProvider->isTokenTypeOnPosition(Lexer::TOKEN_CLOSE_PARENTHESES, $nextPosition);
    }
    private function resolveStartAndEnd(UnionTypeNode $unionTypeNode): ?StartAndEnd
    {
        $starAndEnd = $unionTypeNode->getAttribute(PhpDocAttributeKey::START_AND_END);
        if ($starAndEnd instanceof StartAndEnd) {
            return $starAndEnd;
        }
        // unwrap with parent array type...
        $parentNode = $unionTypeNode->getAttribute(PhpDocAttributeKey::PARENT);
        if (!$parentNode instanceof Node) {
            return null;
        }
        return $parentNode->getAttribute(PhpDocAttributeKey::START_AND_END);
    }
}
