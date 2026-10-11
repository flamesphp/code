<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rector;

use Deprecated;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Const_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeTraverser;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor\CloningVisitor;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\Application\ChangedNodeScopeRefresher;
use Flames\Code\Upgrade\Application\Provider\CurrentFileProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\Comment\CommentsMerger;
use Flames\Code\Upgrade\ChangesReporting\ValueObject\UpgradeWithLineChange;
use Flames\Code\Upgrade\Contract\Rector\HTMLAverseRectorInterface;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeDecorator\CreatedByRuleDecorator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\PhpDocInfoRemovingNodeVisitor;
use Flames\Code\Upgrade\Skipper\Skipper\Skipper;
use Flames\Code\Upgrade\Skipper\ValueObject\SkipMatch;
use Flames\Code\Upgrade\ValueObject\Application\File;
abstract class AbstractRector extends NodeVisitorAbstract implements RectorInterface
{
    private const string EMPTY_NODE_ARRAY_MESSAGE = <<<CODE_SAMPLE
Array of nodes cannot be empty. Ensure "%s->refactor()" returns non-empty array for Nodes.

A) Direct return null for no change:

    return null;

B) Remove the Node:

    return \\Flames\Code\Upgrade\ThirdParty\PhpParser\\NodeVisitor::REMOVE_NODE;
CODE_SAMPLE;
    protected NodeNameResolver $nodeNameResolver;
    protected NodeTypeResolver $nodeTypeResolver;
    protected NodeFactory $nodeFactory;
    protected NodeComparator $nodeComparator;
    /**
     * @internal Use getFile() instead.
     */
    protected File $file;
    protected Skipper $skipper;
    private ChangedNodeScopeRefresher $changedNodeScopeRefresher;
    private SimpleCallableNodeTraverser $simpleCallableNodeTraverser;
    private CurrentFileProvider $currentFileProvider;
    private CommentsMerger $commentsMerger;
    private CreatedByRuleDecorator $createdByRuleDecorator;
    public function autowire(NodeNameResolver $nodeNameResolver, NodeTypeResolver $nodeTypeResolver, SimpleCallableNodeTraverser $simpleCallableNodeTraverser, NodeFactory $nodeFactory, Skipper $skipper, NodeComparator $nodeComparator, CurrentFileProvider $currentFileProvider, CreatedByRuleDecorator $createdByRuleDecorator, ChangedNodeScopeRefresher $changedNodeScopeRefresher, CommentsMerger $commentsMerger): void
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->simpleCallableNodeTraverser = $simpleCallableNodeTraverser;
        $this->nodeFactory = $nodeFactory;
        $this->skipper = $skipper;
        $this->nodeComparator = $nodeComparator;
        $this->currentFileProvider = $currentFileProvider;
        $this->createdByRuleDecorator = $createdByRuleDecorator;
        $this->changedNodeScopeRefresher = $changedNodeScopeRefresher;
        $this->commentsMerger = $commentsMerger;
    }
    /**
     * @return Node[]|null
     *
     * @internal
     */
    final public function beforeTraverse(array $nodes): ?array
    {
        return null;
    }
    /**
     * @return Node[]|null
     *
     * @internal
     */
    final public function afterTraverse(array $nodes)
    {
        return null;
    }
    /**
     * @return NodeVisitor::REMOVE_NODE|Node|null|Node[]
     */
    final public function enterNode(Node $node)
    {
        // keep $this->file populated for BC; refactor() is only ever reached through here
        $this->file = $this->getFile();
        if (is_a($this, HTMLAverseRectorInterface::class, \true) && $this->file->containsHTML()) {
            return null;
        }
        $filePath = $this->file->getFilePath();
        // node already changed by this rule in a previous pass → hard skip
        if ($this->skipper->shouldSkipCurrentNode(static::class, $node)) {
            return null;
        }
        // class/path skip is configured for this rule and file: run the rule on a deep clone to learn
        // whether it would actually have changed anything. Only a skip that prevents a real change
        // counts as used; the original node is left untouched, so the file stays skipped either way.
        $skipMatch = $this->skipper->matchSkip($this, $filePath);
        if ($skipMatch instanceof SkipMatch) {
            if ($this->refactor($this->cloneNode($node)) !== null) {
                $this->skipper->markSkipUsed($skipMatch);
            }
            return null;
        }
        // ensure origNode pulled before refactor to avoid changed during refactor, ref https://3v4l.org/YMEGN
        $originalNode = $node->getAttribute(AttributeKey::ORIGINAL_NODE) ?? $node;
        $refactoredNodeOrState = $this->refactor($node);
        // nothing to change → continue
        if ($refactoredNodeOrState === null) {
            return null;
        }
        if ($refactoredNodeOrState === []) {
            $errorMessage = sprintf(self::EMPTY_NODE_ARRAY_MESSAGE, static::class);
            throw new ShouldNotHappenException($errorMessage);
        }
        $isState = is_int($refactoredNodeOrState);
        if ($isState) {
            $this->createdByRuleDecorator->decorate($node, $originalNode, static::class);
            // only remove node is supported
            if ($refactoredNodeOrState !== NodeVisitor::REMOVE_NODE) {
                // @todo warn about unsupported state in the future
                return null;
            }
            // notify this rule changed code
            $rectorWithLineChange = new UpgradeWithLineChange(static::class, $originalNode->getStartLine());
            $this->file->addRectorClassWithLine($rectorWithLineChange);
            return $refactoredNodeOrState;
        }
        return $this->postRefactorProcess($originalNode, $node, $refactoredNodeOrState, $filePath);
    }
    /**
     * @deprecated no longer used
     * @return mixed[]|int|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node|null
     */
    final public function leaveNode(Node $node)
    {
        return null;
    }
    protected function getFile(): File
    {
        $file = $this->currentFileProvider->getFile();
        if (!$file instanceof File) {
            throw new ShouldNotHappenException('File object is missing. Make sure you call $this->currentFileProvider->setFile(...) before traversing.');
        }
        return $file;
    }
    protected function isName(Node $node, string $name): bool
    {
        return $this->nodeNameResolver->isName($node, $name);
    }
    /**
     * @param string[] $names
     */
    protected function isNames(Node $node, array $names): bool
    {
        return $this->nodeNameResolver->isNames($node, $names);
    }
    /**
     * Some nodes have always-known string name. This makes PHPStan smarter.
     * @see https://phpstan.org/writing-php-code/phpdoc-types#conditional-return-types
     *
     * @return ($node is Node\Param ? string :
     *  ($node is ClassMethod ? string :
     *  ($node is Property ? string :
     *  ($node is PropertyItem ? string :
     *  ($node is Trait_ ? string :
     *  ($node is Interface_ ? string :
     *  ($node is Const_ ? string :
     *  ($node is Node\Const_ ? string :
     *  ($node is Name ? string :
     *      string|null )))))))))
     */
    protected function getName(Node $node): ?string
    {
        return $this->nodeNameResolver->getName($node);
    }
    protected function isObjectType(Node $node, ObjectType $objectType): bool
    {
        return $this->nodeTypeResolver->isObjectType($node, $objectType);
    }
    /**
     * Use this method for getting expr|node type
     */
    protected function getType(Node $node): Type
    {
        return $this->nodeTypeResolver->getType($node);
    }
    /**
     * Use this method for getting native expr type
     */
    protected function getNativeType(Expr $expr): Type
    {
        return $this->nodeTypeResolver->getNativeType($expr);
    }
    /**
     * @param Node|Node[] $nodes
     * @param callable(Node): (int|Node|null|Node[]) $callable
     */
    protected function traverseNodesWithCallable($nodes, callable $callable): void
    {
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($nodes, $callable);
    }
    protected function mirrorComments(Node $newNode, Node $oldNode): void
    {
        $this->commentsMerger->mirrorComments($newNode, $oldNode);
    }
    /**
     * Deep clone, so a skipped rule can be probed on the clone without mutating the real node.
     */
    private function cloneNode(Node $node): Node
    {
        $nodeTraverser = new NodeTraverser(new CloningVisitor(), new PhpDocInfoRemovingNodeVisitor());
        return $nodeTraverser->traverse([$node])[0];
    }
    /**
     * @param Node|Node[] $refactoredNode
     * @return Node|Node[]
     */
    private function postRefactorProcess(Node $originalNode, Node $node, $refactoredNode, string $filePath)
    {
        /** @var non-empty-array<Node>|Node $refactoredNode */
        $this->createdByRuleDecorator->decorate($refactoredNode, $originalNode, static::class);
        $rectorWithLineChange = new UpgradeWithLineChange(static::class, $originalNode->getStartLine());
        $this->file->addRectorClassWithLine($rectorWithLineChange);
        /** @var MutatingScope|null $currentScope */
        $currentScope = $node->getAttribute(AttributeKey::SCOPE);
        $this->refreshScopeNodes($refactoredNode, $filePath, $currentScope);
        return $refactoredNode;
    }
    /**
     * @param Node[]|Node $node
     */
    private function refreshScopeNodes($node, string $filePath, ?MutatingScope $mutatingScope): void
    {
        $nodes = $node instanceof Node ? [$node] : $node;
        foreach ($nodes as $node) {
            $this->changedNodeScopeRefresher->refresh($node, $filePath, $mutatingScope);
        }
    }
}
