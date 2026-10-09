<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use Flames\Code\Upgrade\BetterPhpDocParser\Annotation\AnnotationNaming;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeFinder\PhpDocNodeByTypeFinder;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeMapper;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\BetterPhpDocParser;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Parser\BetterTokenIterator;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\StartAndEnd;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\StaticTypeMapper\Naming\NameScopeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final class PhpDocInfoFactory
{
    /**
     * @var array<int, PhpDocInfo>
     */
    private array $phpDocInfosByObjectId = [];
    public function __construct(private readonly PhpDocNodeMapper $phpDocNodeMapper, private readonly Lexer $lexer, private readonly BetterPhpDocParser $betterPhpDocParser, private readonly StaticTypeMapper $staticTypeMapper, private readonly AnnotationNaming $annotationNaming, private readonly PhpDocNodeByTypeFinder $phpDocNodeByTypeFinder, private readonly NameScopeFactory $nameScopeFactory)
    {
    }
    public function createFromNodeOrEmpty(Node $node): \Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo
    {
        // already added
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo instanceof \Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo) {
            return $phpDocInfo;
        }
        $phpDocInfo = $this->createFromNode($node);
        if ($phpDocInfo instanceof \Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo) {
            return $phpDocInfo;
        }
        return $this->createEmpty($node);
    }
    public function createFromNode(Node $node): ?\Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo
    {
        $objectId = spl_object_id($node);
        if (isset($this->phpDocInfosByObjectId[$objectId])) {
            return $this->phpDocInfosByObjectId[$objectId];
        }
        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            if ($node->getComments() === []) {
                return null;
            }
            // create empty node
            $tokenIterator = new BetterTokenIterator([]);
            $phpDocNode = new PhpDocNode([]);
        } else {
            $tokens = $this->lexer->tokenize($docComment->getText());
            $tokenIterator = new BetterTokenIterator($tokens);
            $phpDocNode = $this->betterPhpDocParser->parseWithNode($tokenIterator, $node);
            $this->setPositionOfLastToken($phpDocNode);
        }
        $phpDocInfo = $this->createFromPhpDocNode($phpDocNode, $tokenIterator, $node);
        $this->phpDocInfosByObjectId[$objectId] = $phpDocInfo;
        return $phpDocInfo;
    }
    /**
     * @api downgrade
     */
    public function createEmpty(Node $node): \Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo
    {
        $phpDocNode = new PhpDocNode([]);
        $phpDocInfo = $this->createFromPhpDocNode($phpDocNode, new BetterTokenIterator([]), $node);
        // multiline by default
        $phpDocInfo->makeMultiLined();
        return $phpDocInfo;
    }
    /**
     * Needed for printing
     */
    private function setPositionOfLastToken(PhpDocNode $phpDocNode): void
    {
        if ($phpDocNode->children === []) {
            return;
        }
        $phpDocChildNodes = $phpDocNode->children;
        $phpDocChildNode = array_pop($phpDocChildNodes);
        $startAndEnd = $phpDocChildNode->getAttribute(PhpDocAttributeKey::START_AND_END);
        if ($startAndEnd instanceof StartAndEnd) {
            $phpDocNode->setAttribute(PhpDocAttributeKey::LAST_PHP_DOC_TOKEN_POSITION, $startAndEnd->getEnd());
        }
    }
    private function createFromPhpDocNode(PhpDocNode $phpDocNode, BetterTokenIterator $betterTokenIterator, Node $node): \Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo
    {
        $this->phpDocNodeMapper->transform($phpDocNode, $betterTokenIterator);
        $phpDocInfo = new \Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo($phpDocNode, $betterTokenIterator, $this->staticTypeMapper, $node, $this->annotationNaming, $this->phpDocNodeByTypeFinder, $this->nameScopeFactory);
        $node->setAttribute(AttributeKey::PHP_DOC_INFO, $phpDocInfo);
        return $phpDocInfo;
    }
}
