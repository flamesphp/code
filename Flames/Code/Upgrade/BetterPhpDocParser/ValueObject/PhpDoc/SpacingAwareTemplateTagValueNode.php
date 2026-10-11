<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDoc;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\TemplateTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use Stringable;
final class SpacingAwareTemplateTagValueNode extends TemplateTagValueNode
{
    public function __construct(string $name, ?TypeNode $typeNode, string $description, private readonly string $preposition)
    {
        parent::__construct($name, $typeNode, $description);
    }
    public function __toString(): string
    {
        // @see https://github.com/rectorphp/rector/issues/3438
        # 'as'/'of'
        $bound = $this->bound instanceof TypeNode ? ' ' . $this->preposition . ' ' . $this->bound : '';
        $content = $this->name . $bound . ' ' . $this->description;
        return trim($content);
    }
}
