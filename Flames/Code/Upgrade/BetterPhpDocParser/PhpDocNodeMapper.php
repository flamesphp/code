<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\DataProvider\CurrentTokenIteratorProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Parser\BetterTokenIterator;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\CloningPhpDocNodeVisitor;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeVisitor\ParentConnectingPhpDocNodeVisitor;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Tests\BetterPhpDocParser\PhpDocNodeMapperTest
 */
final readonly class PhpDocNodeMapper
{
    private PhpDocNodeTraverser $phpDocNodeTraverser;
    /**
     * @param BasePhpDocNodeVisitorInterface[] $phpDocNodeVisitors
     */
    public function __construct(private CurrentTokenIteratorProvider $currentTokenIteratorProvider, ParentConnectingPhpDocNodeVisitor $parentConnectingPhpDocNodeVisitor, CloningPhpDocNodeVisitor $cloningPhpDocNodeVisitor, private array $phpDocNodeVisitors)
    {
        Assert::notEmpty($this->phpDocNodeVisitors);
        $this->phpDocNodeTraverser = new PhpDocNodeTraverser();
        $this->phpDocNodeTraverser->addPhpDocNodeVisitor($parentConnectingPhpDocNodeVisitor);
        $this->phpDocNodeTraverser->addPhpDocNodeVisitor($cloningPhpDocNodeVisitor);
        foreach ($this->phpDocNodeVisitors as $phpDocNodeVisitor) {
            $this->phpDocNodeTraverser->addPhpDocNodeVisitor($phpDocNodeVisitor);
        }
    }
    public function transform(PhpDocNode $phpDocNode, BetterTokenIterator $betterTokenIterator): void
    {
        $this->currentTokenIteratorProvider->setBetterTokenIterator($betterTokenIterator);
        $this->phpDocNodeTraverser->traverse($phpDocNode);
    }
}
