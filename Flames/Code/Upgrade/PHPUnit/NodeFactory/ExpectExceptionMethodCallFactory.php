<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\PHPUnit\PhpDoc\PhpDocValueToNodeMapper;
final readonly class ExpectExceptionMethodCallFactory
{
    public function __construct(private NodeFactory $nodeFactory, private PhpDocValueToNodeMapper $phpDocValueToNodeMapper)
    {
    }
    /**
     * @param PhpDocTagNode[] $phpDocTagNodes
     * @return Expression[]
     */
    public function createFromTagValueNodes(array $phpDocTagNodes, string $methodName): array
    {
        $methodCallExpressions = [];
        foreach ($phpDocTagNodes as $phpDocTagNode) {
            $methodCall = $this->createMethodCall($phpDocTagNode, $methodName);
            $methodCallExpressions[] = new Expression($methodCall);
        }
        return $methodCallExpressions;
    }
    private function createMethodCall(PhpDocTagNode $phpDocTagNode, string $methodName): MethodCall
    {
        if (!$phpDocTagNode->value instanceof GenericTagValueNode) {
            throw new ShouldNotHappenException();
        }
        $expr = $this->createExpectedExpr($phpDocTagNode, $phpDocTagNode->value);
        return $this->nodeFactory->createMethodCall('this', $methodName, [new Arg($expr)]);
    }
    private function createExpectedExpr(PhpDocTagNode $phpDocTagNode, GenericTagValueNode $genericTagValueNode): Expr
    {
        if ($phpDocTagNode->name === '@expectedExceptionMessage') {
            return new String_($genericTagValueNode->value);
        }
        return $this->phpDocValueToNodeMapper->mapGenericTagValueNode($genericTagValueNode);
    }
}
