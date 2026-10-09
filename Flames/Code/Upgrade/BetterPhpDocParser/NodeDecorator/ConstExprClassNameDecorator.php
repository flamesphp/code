<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\NodeDecorator;

use PhpParser\Node as PhpNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
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
        $this->phpDocNodeTraverser->traverseWithCallable($phpDocNode, '', function (Node $node) use ($phpNode): ?\PHPStan\PhpDocParser\Ast\Node {
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
