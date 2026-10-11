<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\NodeDecorator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node as PhpNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Node;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\GenericTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\PhpDocParser\PhpDocNodeDecoratorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\StaticTypeMapper\Naming\NameScopeFactory;
/**
 * Decorate node with fully qualified class name for generic annotations for @uses, @used-by, and @see
 * e.g. @uses Direction::*
 *
 * @see https://docs.phpdoc.org/guide/references/phpdoc/tags/uses.html
 */
final readonly class PhpDocTagGenericUsesDecorator implements PhpDocNodeDecoratorInterface
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
            if (!$node instanceof PhpDocTagNode) {
                return null;
            }
            if (!$node->value instanceof GenericTagValueNode) {
                return null;
            }
            if (!in_array($node->name, ['@uses', '@used-by', '@see'], \true)) {
                return null;
            }
            $reference = $node->value->value;
            if (!str_contains($reference, '::')) {
                return null;
            }
            if ($node->value->hasAttribute(PhpDocAttributeKey::RESOLVED_CLASS)) {
                return null;
            }
            $classValue = explode('::', $reference)[0];
            $className = $this->resolveFullyQualifiedClass($classValue, $phpNode);
            $node->value->setAttribute(PhpDocAttributeKey::RESOLVED_CLASS, $className);
            return $node;
        });
    }
    private function resolveFullyQualifiedClass(string $classValue, PhpNode $phpNode): string
    {
        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($phpNode);
        return $nameScope->resolveStringName($classValue);
    }
}
