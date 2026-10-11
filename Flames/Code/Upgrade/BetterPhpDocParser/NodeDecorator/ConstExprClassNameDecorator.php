<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\NodeDecorator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node as PhpNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\ConstExpr\ConstFetchNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\PhpDocParser\PhpDocNodeDecoratorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\StaticTypeMapper\Naming\NameScopeFactory;
/**
 * Decorate node with fully qualified class name for const epxr,
 * e.g. Direction::*
 */
final readonly class ConstExprClassNameDecorator implements PhpDocNodeDecoratorInterface
{
    public function __construct(private NameScopeFactory $nameScopeFactory, private PhpDocNodeTraverser $phpDocNodeTraverser)
    {
    }
    public function decorate(PhpDocNode $phpDocNode, PhpNode $phpNode): void
    {
        // iterating all phpdocs has big overhead. peek into the phpdoc to exit early
        if (!str_contains($phpDocNode->__toString(), '::')) {
            return;
        }
        $this->phpDocNodeTraverser->traverseWithCallable($phpDocNode, '', function (Node $node) use ($phpNode): ?\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node {
            if (!$node instanceof ConstFetchNode) {
                return null;
            }
            $className = $this->resolveFullyQualifiedClass($node, $phpNode);
            $node->setAttribute(PhpDocAttributeKey::RESOLVED_CLASS, $className);
            return $node;
        });
    }
    private function resolveFullyQualifiedClass(ConstFetchNode $constFetchNode, PhpNode $phpNode): string
    {
        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($phpNode);
        return $nameScope->resolveStringName($constFetchNode->className);
    }
}
