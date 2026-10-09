<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor;

use PHPStan\PhpDocParser\Ast\Node;
final class CallablePhpDocNodeVisitor extends \Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor
{
    /**
     * @var callable(Node, string|null): (int|null|Node)
     */
    private $callable;
    /**
     * @param callable(Node $callable, string|null $docContent): (int|null|Node) $callable
     */
    public function __construct(callable $callable, private readonly ?string $docContent)
    {
        $this->callable = $callable;
    }
    /**
     * @return int|\PHPStan\PhpDocParser\Ast\Node|null
     */
    public function enterNode(Node $node)
    {
        $callable = $this->callable;
        return $callable($node, $this->docContent);
    }
}
