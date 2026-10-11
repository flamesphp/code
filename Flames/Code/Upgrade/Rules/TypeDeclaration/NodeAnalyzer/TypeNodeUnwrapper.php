<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\IntersectionType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UnionType;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
final readonly class TypeNodeUnwrapper
{
    public function __construct(private NodeComparator $nodeComparator)
    {
    }
    /**
     * @param array<UnionType|NullableType|Name|Identifier|IntersectionType> $typeNodes
     * @return array<Name|Identifier>
     */
    public function unwrapNullableUnionTypes(array $typeNodes): array
    {
        $unwrappedTypeNodes = [];
        foreach ($typeNodes as $typeNode) {
            if ($typeNode instanceof UnionType) {
                $unwrappedTypeNodes = array_merge($unwrappedTypeNodes, $this->unwrapNullableUnionTypes($typeNode->types));
            } elseif ($typeNode instanceof NullableType) {
                $unwrappedTypeNodes[] = $typeNode->type;
                $unwrappedTypeNodes[] = new Identifier('null');
            } elseif ($typeNode instanceof IntersectionType) {
                $unwrappedTypeNodes = array_merge($unwrappedTypeNodes, $this->unwrapNullableUnionTypes($typeNode->types));
            } else {
                $unwrappedTypeNodes[] = $typeNode;
            }
        }
        return $this->uniquateNodes($unwrappedTypeNodes);
    }
    /**
     * @template TNode as Node
     *
     * @param TNode[] $nodes
     * @return TNode[]
     */
    public function uniquateNodes(array $nodes): array
    {
        $uniqueNodes = [];
        foreach ($nodes as $node) {
            $uniqueHash = $this->nodeComparator->printWithoutComments($node);
            $uniqueNodes[$uniqueHash] = $node;
        }
        // reset keys from 0, for further compatibility
        return array_values($uniqueNodes);
    }
}
