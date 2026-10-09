<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Printer\BetterStandardPrinter;
final readonly class DoctrineAnnotationFactory
{
    public function __construct(private BetterStandardPrinter $betterStandardPrinter)
    {
    }
    /**
     * @api downgrade
     */
    public function createFromAttribute(Attribute $attribute, string $className): DoctrineAnnotationTagValueNode
    {
        $items = $this->createItemsFromArgs($attribute->args);
        $identifierTypeNode = new IdentifierTypeNode($className);
        return new DoctrineAnnotationTagValueNode($identifierTypeNode, null, $items);
    }
    /**
     * @param Arg[] $args
     * @return mixed[]
     */
    private function createItemsFromArgs(array $args): array
    {
        $items = [];
        foreach ($args as $arg) {
            if ($arg->value instanceof String_) {
                // standardize double quotes for annotations
                $arg->value->setAttribute(AttributeKey::KIND, String_::KIND_DOUBLE_QUOTED);
            }
            $itemValue = $this->betterStandardPrinter->print($arg->value);
            if ($arg->name instanceof Identifier) {
                $name = $this->betterStandardPrinter->print($arg->name);
                $items[$name] = $itemValue;
            } else {
                $items[] = $itemValue;
            }
        }
        return $items;
    }
}
