<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
final readonly class AliasUsesResolver
{
    public function __construct(private \Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\UseImportsTraverser $useImportsTraverser)
    {
    }
    /**
     * @param Stmt[] $stmts
     * @return string[]
     */
    public function resolveFromNode(Node $node, array $stmts): array
    {
        if (!$node instanceof Namespace_ && !$node instanceof FileNode) {
            /** @var Namespace_[]|FileNode[] $namespaces */
            $namespaces = array_filter($stmts, static fn(Stmt $stmt): bool => $stmt instanceof Namespace_ || $stmt instanceof FileNode);
            if (count($namespaces) !== 1) {
                return [];
            }
            $node = current($namespaces);
        }
        return $this->resolveFromStmts($node->stmts);
    }
    /**
     * @param Stmt[] $stmts
     * @return string[]
     */
    public function resolveFromStmts(array $stmts): array
    {
        $aliasedUses = [];
        /** @param Use_::TYPE_* $useType */
        $this->useImportsTraverser->traverserStmts($stmts, static function (int $useType, UseItem $useItem, string $name) use (&$aliasedUses): void {
            if ($useType !== Use_::TYPE_NORMAL) {
                return;
            }
            if (!$useItem->alias instanceof Identifier) {
                return;
            }
            $aliasedUses[] = $name;
        });
        return $aliasedUses;
    }
}
