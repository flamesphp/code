<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Rector;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment\Doc;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Declare_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Nop;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
final class UnusedImportRemovingPostRector extends AbstractPostRector
{
    public function __construct(private readonly SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Namespace_ && !$node instanceof FileNode) {
            return null;
        }
        $hasChanged = \false;
        $namespaceOriginalCase = $node instanceof Namespace_ && $node->name instanceof Name ? $node->name->toString() : null;
        $namesInOriginalCase = $this->resolveUsedPhpAndDocNames($node);
        $namesInLowerCase = array_map(\strtolower(...), $namesInOriginalCase);
        $firstStmtKey = 0;
        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Declare_ && $key === 0) {
                ++$firstStmtKey;
            }
            if (!$stmt instanceof Use_) {
                continue;
            }
            if ($stmt->uses === [] || $namesInOriginalCase === []) {
                unset($node->stmts[$key]);
                $hasChanged = \true;
                continue;
            }
            $isCaseSensitive = $stmt->type === Use_::TYPE_CONSTANT;
            $names = $isCaseSensitive ? $namesInOriginalCase : $namesInLowerCase;
            $namespaceName = $namespaceOriginalCase === null ? null : ($isCaseSensitive ? $namespaceOriginalCase : strtolower($namespaceOriginalCase));
            foreach ($stmt->uses as $useUseKey => $useUse) {
                if ($this->isUseImportUsed($useUse, $isCaseSensitive, $names, $namespaceName)) {
                    continue;
                }
                unset($stmt->uses[$useUseKey]);
                $hasChanged = \true;
            }
            if ($stmt->uses === []) {
                $comments = $node->stmts[$key]->getComments();
                if ($key === $firstStmtKey && $comments !== []) {
                    $node->stmts[$key] = new Nop();
                    $node->stmts[$key]->setAttribute(AttributeKey::COMMENTS, $comments);
                } else {
                    unset($node->stmts[$key]);
                }
            }
        }
        if ($hasChanged === \false) {
            return null;
        }
        $this->addRectorClassWithLine($node);
        $node->stmts = array_values($node->stmts);
        return $node;
    }
    /**
     * @return string[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_|\Flames\Code\Upgrade\PhpParser\Node\FileNode $fileNode
     */
    private function findNonUseImportNames($fileNode): array
    {
        $names = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($fileNode->stmts, static function (Node $node) use (&$names) {
            if ($node instanceof Use_) {
                return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Name) {
                return null;
            }
            if ($node instanceof FullyQualified) {
                $originalName = $node->getAttribute(AttributeKey::ORIGINAL_NAME);
                if ($originalName instanceof Name) {
                    // collect original Name as cover namespaced used
                    $names[] = $originalName->toString();
                    return $node;
                }
            }
            $names[] = $node->toString();
            return $node;
        });
        return $names;
    }
    /**
     * @return string[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_|\Flames\Code\Upgrade\PhpParser\Node\FileNode $rootNode
     */
    private function findNamesInDocBlocks($rootNode): array
    {
        $names = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($rootNode, function (Node $node) use (&$names) {
            $comments = $node->getComments();
            if ($comments === []) {
                return null;
            }
            $docs = array_filter($comments, static fn(Comment $comment): bool => $comment instanceof Doc);
            if ($docs === []) {
                return null;
            }
            $totalDocs = count($docs);
            foreach ($docs as $doc) {
                $nodeToCheck = $totalDocs === 1 ? $node : clone $node;
                if ($totalDocs > 1) {
                    $nodeToCheck->setDocComment($doc);
                }
                $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($nodeToCheck);
                $names = array_merge($names, $phpDocInfo->getAnnotationClassNames());
                $constFetchNodeNames = $phpDocInfo->getConstFetchNodeClassNames();
                $names = array_merge($names, $constFetchNodeNames);
                $genericTagClassNames = $phpDocInfo->getGenericTagClassNames();
                $names = array_merge($names, $genericTagClassNames);
                $inlineGenericUsesTagClassNames = $phpDocInfo->getInlineGenericUsesTagClassNames();
                $names = array_merge($names, $inlineGenericUsesTagClassNames);
                $arrayItemTagClassNames = $phpDocInfo->getArrayItemNodeClassNames();
                $names = array_merge($names, $arrayItemTagClassNames);
            }
        });
        return $names;
    }
    /**
     * @return string[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Namespace_|\Flames\Code\Upgrade\PhpParser\Node\FileNode $rootNode
     */
    private function resolveUsedPhpAndDocNames($rootNode): array
    {
        $phpNames = $this->findNonUseImportNames($rootNode);
        $docBlockNames = $this->findNamesInDocBlocks($rootNode);
        $names = array_merge($phpNames, $docBlockNames);
        return array_unique($names);
    }
    /**
     * @param string[] $names
     */
    private function isUseImportUsed(UseItem $useItem, bool $isCaseSensitive, array $names, ?string $namespaceName): bool
    {
        $comparedName = $useItem->alias instanceof Identifier ? $useItem->alias->toString() : $useItem->name->toString();
        if (!$isCaseSensitive) {
            $comparedName = strtolower($comparedName);
        }
        if (in_array($comparedName, $names, \true)) {
            return \true;
        }
        $lastName = Strings::after($comparedName, '\\', -1);
        $namespacedPrefix = $lastName . '\\';
        if ($namespacedPrefix === '\\') {
            $namespacedPrefix = $comparedName . '\\';
        }
        // match partial import
        foreach ($names as $name) {
            if (str_starts_with($name, '\\')) {
                continue;
            }
            if ($this->isSubNamespace($name, $comparedName, $namespacedPrefix)) {
                return \true;
            }
            if (!str_starts_with($name, $lastName . '\\')) {
                if (str_starts_with($name, $comparedName . '\\')) {
                    return \true;
                }
                continue;
            }
            if ($namespaceName === null) {
                return \true;
            }
            if (!str_starts_with($name, $namespaceName . '\\')) {
                return \true;
            }
        }
        return \false;
    }
    private function isSubNamespace(string $name, string $comparedName, string $namespacedPrefix): bool
    {
        // a partially qualified name like "Foo\Bar" resolves through the import of its first
        // segment, never through an import whose tail it happens to match, so only a single
        // segment name (the imported short name) may be matched against the import's tail here
        if (!str_contains($name, '\\') && str_ends_with($comparedName, '\\' . $name)) {
            return \true;
        }
        if (str_starts_with($name, $namespacedPrefix)) {
            $subNamespace = (string) substr($name, strlen($namespacedPrefix));
            return !str_contains($subNamespace, '\\');
        }
        return \false;
    }
}
