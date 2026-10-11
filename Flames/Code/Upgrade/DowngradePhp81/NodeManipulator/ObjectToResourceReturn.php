<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp81\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\BooleanOr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
final readonly class ObjectToResourceReturn
{
    private const string IS_INSTANCEOF_IN_BINARYOP = 'is_instanceof_in_binaryop';
    public function __construct(private NodeNameResolver $nodeNameResolver, private NodeFactory $nodeFactory, private BetterNodeFinder $betterNodeFinder, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private NodeComparator $nodeComparator)
    {
    }
    /**
     * @param string[] $collectionObjectToResource
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Instanceof_ $instanceof
     */
    public function refactor($instanceof, array $collectionObjectToResource): ?BooleanOr
    {
        if ($instanceof instanceof BinaryOp) {
            $this->setIsInstanceofInBinaryOpAttribute($instanceof);
            return null;
        }
        if ($instanceof->getAttribute(self::IS_INSTANCEOF_IN_BINARYOP) === \true) {
            return null;
        }
        if (!$instanceof->class instanceof FullyQualified) {
            return null;
        }
        $className = $instanceof->class->toString();
        foreach ($collectionObjectToResource as $singleCollectionObjectToResource) {
            if ($singleCollectionObjectToResource !== $className) {
                continue;
            }
            return new BooleanOr($this->nodeFactory->createFuncCall('is_resource', [$instanceof->expr]), $instanceof);
        }
        return null;
    }
    private function setIsInstanceofInBinaryOpAttribute(BinaryOp $binaryOp): void
    {
        $node = $this->betterNodeFinder->findFirst($binaryOp, function (Node $subNode): bool {
            if (!$subNode instanceof FuncCall) {
                return \false;
            }
            if (!$subNode->name instanceof Name) {
                return \false;
            }
            if (!$this->nodeNameResolver->isName($subNode->name, 'is_resource')) {
                return \false;
            }
            if ($subNode->isFirstClassCallable()) {
                return \false;
            }
            $args = $subNode->getArgs();
            return isset($args[0]);
        });
        if (!$node instanceof FuncCall) {
            return;
        }
        /** @var Arg $currentArg */
        $currentArg = $node->getArgs()[0];
        $currentArgValue = $currentArg->value;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($binaryOp, function (Node $subNode) use ($currentArgValue): ?Instanceof_ {
            if ($subNode instanceof Instanceof_ && $this->nodeComparator->areNodesEqual($currentArgValue, $subNode->expr)) {
                $subNode->setAttribute(self::IS_INSTANCEOF_IN_BINARYOP, \true);
                return $subNode;
            }
            return null;
        });
    }
}
