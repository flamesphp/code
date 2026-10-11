<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation;

use Flames\Code\Upgrade\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Stringable;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final class CurlyListNode extends \Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\AbstractValuesAwareNode
{
    /**
     * @param ArrayItemNode[] $arrayItemNodes
     */
    public function __construct(/**
     * @readonly
     */
    private readonly array $arrayItemNodes = [])
    {
        Assert::allIsInstanceOf($this->arrayItemNodes, ArrayItemNode::class);
        parent::__construct($this->arrayItemNodes);
    }
    public function __toString(): string
    {
        // possibly list items
        return $this->implode($this->values);
    }
    /**
     * @param ArrayItemNode[] $array
     */
    private function implode(array $array): string
    {
        $itemContents = '';
        $lastItemKey = array_key_last($array);
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $itemContents .= (string) $value;
            } else {
                $itemContents .= $key . '=' . $value;
            }
            if ($lastItemKey !== $key) {
                $itemContents .= ', ';
            }
        }
        return '{' . $itemContents . '}';
    }
}
