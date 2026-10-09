<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Rules\CodingStyle\NodeAnalyzer\UseImportNameMatcher;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\ValueObject\Application\File;
/**
 * @see \Flames\Code\Upgrade\Tests\CodingStyle\ClassNameImport\ShortNameResolver\ShortNameResolverTest
 */
final class ShortNameResolver
{
    /**
     * @var array<string, string[]>
     */
    private array $shortNamesByFilePath = [];
    private ?File $shortClassLikeNamesFile = null;
    private int $shortClassLikeNamesVersion = 0;
    /**
     * @var string[]
     */
    private array $shortClassLikeNames = [];
    public function __construct(private readonly SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private readonly NodeNameResolver $nodeNameResolver, private readonly BetterNodeFinder $betterNodeFinder, private readonly UseImportNameMatcher $useImportNameMatcher, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    /**
     * @return array<string, string>
     */
    public function resolveFromFile(File $file): array
    {
        $filePath = $file->getFilePath();
        if (isset($this->shortNamesByFilePath[$filePath])) {
            return $this->shortNamesByFilePath[$filePath];
        }
        $shortNamesToFullyQualifiedNames = $this->resolveForStmts($file->getNewStmts());
        $this->shortNamesByFilePath[$filePath] = $shortNamesToFullyQualifiedNames;
        return $shortNamesToFullyQualifiedNames;
    }
    /**
     * Collects all "class <SomeClass>", "trait <SomeTrait>" and "interface <SomeInterface>"
     * @return string[]
     */
    public function resolveShortClassLikeNames(File $file): array
    {
        // asked for every imported name, while the class-likes only change with a new version of the file stmts
        if ($file === $this->shortClassLikeNamesFile && $file->getNewStmtsVersion() === $this->shortClassLikeNamesVersion) {
            return $this->shortClassLikeNames;
        }
        $this->shortClassLikeNamesFile = $file;
        $this->shortClassLikeNamesVersion = $file->getNewStmtsVersion();
        $this->shortClassLikeNames = $this->findShortClassLikeNames($file);
        return $this->shortClassLikeNames;
    }
    /**
     * @return string[]
     */
    private function findShortClassLikeNames(File $file): array
    {
        $rootNode = $file->getUseImportsRootNode();
        // nothing to resolve
        if (!$rootNode instanceof Node) {
            return [];
        }
        /** @var ClassLike[] $classLikes */
        $classLikes = $this->betterNodeFinder->findInstanceOf($rootNode->stmts, ClassLike::class);
        $shortClassLikeNames = [];
        foreach ($classLikes as $classLike) {
            $shortClassLikeNames[] = $this->nodeNameResolver->getShortName($classLike);
        }
        return array_unique($shortClassLikeNames);
    }
    /**
     * @param Stmt[] $stmts
     * @return array<string, string>
     */
    private function resolveForStmts(array $stmts): array
    {
        $shortNamesToFullyQualifiedNames = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($stmts, function (Node $node) use (&$shortNamesToFullyQualifiedNames) {
            // class name is used!
            if ($node instanceof ClassLike && $node->name instanceof Identifier) {
                $fullyQualifiedName = $this->nodeNameResolver->getName($node);
                if ($fullyQualifiedName === null) {
                    return null;
                }
                $shortNamesToFullyQualifiedNames[$node->name->toString()] = $fullyQualifiedName;
                return null;
            }
            if (!$node instanceof Name) {
                return null;
            }
            $originalName = $node->getAttribute(AttributeKey::ORIGINAL_NAME);
            if (!$originalName instanceof Name) {
                return null;
            }
            // already short
            if (str_contains($originalName->toString(), '\\')) {
                return null;
            }
            $shortNamesToFullyQualifiedNames[$originalName->toString()] = $this->nodeNameResolver->getName($node);
            return null;
        });
        $docBlockShortNamesToFullyQualifiedNames = $this->resolveFromStmtsDocBlocks($stmts);
        /** @var array<string, string> $result */
        $result = array_merge($shortNamesToFullyQualifiedNames, $docBlockShortNamesToFullyQualifiedNames);
        return $result;
    }
    /**
     * @param Stmt[] $stmts
     * @return array<string, string>
     */
    private function resolveFromStmtsDocBlocks(array $stmts): array
    {
        $shortNames = [];
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($stmts, function (Node $node) use (&$shortNames) {
            // speed up for nodes that are
            $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
            if (!$phpDocInfo instanceof PhpDocInfo) {
                return null;
            }
            $phpDocNodeTraverser = new PhpDocNodeTraverser();
            $phpDocNodeTraverser->traverseWithCallable($phpDocInfo->getPhpDocNode(), '', static function ($node) use (&$shortNames) {
                if ($node instanceof PhpDocTagNode) {
                    $shortName = trim($node->name, '@');
                    if (ucfirst($shortName) === $shortName) {
                        $shortNames[] = $shortName;
                    }
                    return null;
                }
                if ($node instanceof IdentifierTypeNode) {
                    $shortNames[] = $node->name;
                }
                return null;
            });
            return null;
        });
        return $this->fqnizeShortNames($shortNames, $stmts);
    }
    /**
     * @param string[] $shortNames
     * @param Stmt[] $stmts
     * @return array<string, string>
     */
    private function fqnizeShortNames(array $shortNames, array $stmts): array
    {
        $shortNamesToFullyQualifiedNames = [];
        foreach ($shortNames as $shortName) {
            $stmtsMatchedName = $this->useImportNameMatcher->matchNameWithStmts($shortName, $stmts);
            if ($stmtsMatchedName == null) {
                continue;
            }
            $shortNamesToFullyQualifiedNames[$shortName] = $stmtsMatchedName;
        }
        return $shortNamesToFullyQualifiedNames;
    }
}
