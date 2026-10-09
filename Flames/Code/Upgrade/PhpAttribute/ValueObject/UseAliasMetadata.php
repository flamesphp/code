<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpAttribute\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
final readonly class UseAliasMetadata
{
    public function __construct(private string $shortAttributeName, private string $useImportName, private UseItem $useItem)
    {
    }
    public function getShortAttributeName(): string
    {
        return $this->shortAttributeName;
    }
    public function getUseImportName(): string
    {
        return $this->useImportName;
    }
    public function getUseUse(): UseItem
    {
        return $this->useItem;
    }
}
