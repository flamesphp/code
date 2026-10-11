<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('inFunctionNode')]
final class InFunctionNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpFunctionFromParserNodeReflection $functionReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Stmt\Function_ $originalNode;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpFunctionFromParserNodeReflection $functionReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node\Stmt\Function_ $originalNode
    )
    {
        $this->functionReflection = $functionReflection;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getFunctionReflection(): PhpFunctionFromParserNodeReflection
    {
        return $this->functionReflection;
    }
    public function getOriginalNode(): Node\Stmt\Function_
    {
        return $this->originalNode;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Stmt_InFunctionNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
}
