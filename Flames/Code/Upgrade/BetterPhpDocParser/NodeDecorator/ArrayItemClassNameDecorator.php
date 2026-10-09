<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\NodeDecorator;

use PhpParser\Node as PhpNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\PhpDocParser\PhpDocNodeDecoratorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\StaticTypeMapper\Naming\NameScopeFactory;
/**
 * Decorate node with fully qualified class name for annotation:
 * e.g. @ORM\Column(type=Types::STRING, length=100, nullable=false)
 */
final readonly class ArrayItemClassNameDecorator implements PhpDocNodeDecoratorInterface
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
            if (!$node instanceof ArrayItemNode) {
                return null;
            }
            $valueClassName = $this->resolveClassFromScopeResolution($node->value, $phpNode);
            if ($valueClassName !== null) {
                $node->setAttribute(PhpDocAttributeKey::RESOLVED_CLASS, $valueClassName);
            }
            // e.g. @ORM\DiscriminatorMap({ SomeEnum::TOTP = "..." }), the class is in the key
            $keyClassName = $this->resolveClassFromScopeResolution($node->key, $phpNode);
            if ($keyClassName !== null) {
                $node->setAttribute(PhpDocAttributeKey::RESOLVED_KEY_CLASS, $keyClassName);
            }
            return $node;
        });
    }
    /**
     * @param mixed $value
     */
    private function resolveClassFromScopeResolution($value, PhpNode $phpNode): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $splitScopeResolution = explode('::', $value);
        if (count($splitScopeResolution) !== 2) {
            return null;
        }
        return $this->resolveFullyQualifiedClass($splitScopeResolution[0], $phpNode);
    }
    private function resolveFullyQualifiedClass(string $className, PhpNode $phpNode): string
    {
        $nameScope = $this->nameScopeFactory->createNameScopeFromNodeWithoutTemplateTypes($phpNode);
        return $nameScope->resolveStringName($className);
    }
}
