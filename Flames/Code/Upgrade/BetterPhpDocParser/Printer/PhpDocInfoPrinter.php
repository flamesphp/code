<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\Printer;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\InlineHTML;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocChildNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PhpDocTagNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\PropertyTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ThrowsTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Lexer\Lexer;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor\ChangedPhpDocNodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\StartAndEnd;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
use Flames\Code\Upgrade\Util\StringUtils;
/**
 * @see \Flames\Code\Upgrade\Tests\BetterPhpDocParser\PhpDocInfo\PhpDocInfoPrinter\PhpDocInfoPrinterTest
 */
final class PhpDocInfoPrinter
{
    /**
     * @see https://regex101.com/r/Ab0Vey/1
     */
    private const string CLOSING_DOCBLOCK_REGEX = '#\*\/(\s+)?$#';
    /**
     * @see https://regex101.com/r/5fJyws/1
     */
    private const string CALLABLE_REGEX = '#callable(\s+)\(#';
    /**
     * @var string[]
     */
    private const array DOCBLOCK_STARTS = ['//', '/**', '/*', '#'];
    /**
     * @var string Uses a hardcoded unix-newline since most codes use it (even on windows) - otherwise we would need to normalize newlines
     */
    private const string NEWLINE_WITH_ASTERISK = "\n" . ' *';
    /**
     * @see https://regex101.com/r/ME5Fcn/1
     */
    private const string NEW_LINE_WITH_SPACE_REGEX = "# (?<new_line>\r\n|\n)#";
    private int $tokenCount = 0;
    private int $currentTokenPosition = 0;
    /**
     * @var mixed[]
     */
    private array $tokens = [];
    private ?PhpDocInfo $phpDocInfo = null;
    private readonly PhpDocNodeTraverser $changedPhpDocNodeTraverser;
    public function __construct(private readonly \Flames\Code\Upgrade\BetterPhpDocParser\Printer\EmptyPhpDocDetector $emptyPhpDocDetector, private readonly \Flames\Code\Upgrade\BetterPhpDocParser\Printer\DocBlockInliner $docBlockInliner, private readonly \Flames\Code\Upgrade\BetterPhpDocParser\Printer\RemoveNodesStartAndEndResolver $removeNodesStartAndEndResolver, private readonly ChangedPhpDocNodeVisitor $changedPhpDocNodeVisitor)
    {
        $changedPhpDocNodeTraverser = new PhpDocNodeTraverser();
        $changedPhpDocNodeTraverser->addPhpDocNodeVisitor($this->changedPhpDocNodeVisitor);
        $this->changedPhpDocNodeTraverser = $changedPhpDocNodeTraverser;
    }
    public function printNew(PhpDocInfo $phpDocInfo): string
    {
        $docContent = (string) $phpDocInfo->getPhpDocNode();
        if ($phpDocInfo->isSingleLine()) {
            return $this->docBlockInliner->inline($docContent);
        }
        if ($phpDocInfo->getNode() instanceof InlineHTML) {
            return '<?php' . \PHP_EOL . $docContent . \PHP_EOL . '?>';
        }
        return $docContent;
    }
    /**
     * As in php-parser
     *
     * ref: https://github.com/nikic/PHP-Parser/issues/487#issuecomment-375986259
     * - Tokens[node.startPos .. subnode1.startPos]
     * - Print(subnode1)
     * - Tokens[subnode1.endPos .. subnode2.startPos]
     * - Print(subnode2)
     * - Tokens[subnode2.endPos .. node.endPos]
     */
    public function printFormatPreserving(PhpDocInfo $phpDocInfo): string
    {
        if ($phpDocInfo->getTokens() === []) {
            // completely new one, just print string version of it
            if ($phpDocInfo->getPhpDocNode()->children === []) {
                return '';
            }
            if ($phpDocInfo->getNode() instanceof InlineHTML) {
                return '<?php' . \PHP_EOL . $phpDocInfo->getPhpDocNode() . \PHP_EOL . '?>';
            }
            return (string) $phpDocInfo->getPhpDocNode();
        }
        $phpDocNode = $phpDocInfo->getPhpDocNode();
        $this->tokens = $phpDocInfo->getTokens();
        $this->tokenCount = $phpDocInfo->getTokenCount();
        $this->phpDocInfo = $phpDocInfo;
        $this->currentTokenPosition = 0;
        $phpDocString = $this->printPhpDocNode($phpDocNode);
        // hotfix of extra space with callable ()
        return Strings::replace($phpDocString, self::CALLABLE_REGEX, 'callable(');
    }
    /**
     * @return Comment[]
     */
    public function printToComments(PhpDocInfo $phpDocInfo): array
    {
        $printedPhpDocContents = $this->printFormatPreserving($phpDocInfo);
        return [new Comment($printedPhpDocContents)];
    }
    private function getCurrentPhpDocInfo(): PhpDocInfo
    {
        if (!$this->phpDocInfo instanceof PhpDocInfo) {
            throw new ShouldNotHappenException();
        }
        return $this->phpDocInfo;
    }
    private function printPhpDocNode(PhpDocNode $phpDocNode): string
    {
        // no nodes were, so empty doc
        if ($this->emptyPhpDocDetector->isPhpDocNodeEmpty($phpDocNode)) {
            return '';
        }
        $output = '';
        // node output
        $nodeCount = count($phpDocNode->children);
        foreach ($phpDocNode->children as $key => $phpDocChildNode) {
            $output .= $this->printDocChildNode($phpDocChildNode, $key + 1, $nodeCount);
        }
        $output = $this->printEnd($output);
        // fix missing start
        if (!$this->hasDocblockStart($output) && $output !== '') {
            $output = '/**' . $output;
        }
        // fix missing end
        if (str_starts_with($output, '/**') && !StringUtils::isMatch($output, self::CLOSING_DOCBLOCK_REGEX)) {
            $output .= ' */';
        }
        return Strings::replace($output, self::NEW_LINE_WITH_SPACE_REGEX, static fn(array $match): string => (string) $match['new_line']);
    }
    private function hasDocblockStart(string $output): bool
    {
        $found = array_any(self::DOCBLOCK_STARTS, fn($docblockStart) => str_starts_with($output, $docblockStart));
        return $found;
    }
    private function printDocChildNode(PhpDocChildNode $phpDocChildNode, int $key = 0, int $nodeCount = 0): string
    {
        $output = '';
        $shouldReprintChildNode = $this->shouldReprint($phpDocChildNode);
        if ($phpDocChildNode instanceof PhpDocTagNode && ($shouldReprintChildNode && ($phpDocChildNode->value instanceof ParamTagValueNode || $phpDocChildNode->value instanceof ThrowsTagValueNode || $phpDocChildNode->value instanceof VarTagValueNode || $phpDocChildNode->value instanceof ReturnTagValueNode || $phpDocChildNode->value instanceof PropertyTagValueNode))) {
            // the type has changed → reprint
            $phpDocChildNodeStartEnd = $phpDocChildNode->getAttribute(PhpDocAttributeKey::START_AND_END);
            // bump the last position of token after just printed node
            if ($phpDocChildNodeStartEnd instanceof StartAndEnd) {
                $this->currentTokenPosition = $phpDocChildNodeStartEnd->getEnd();
            }
            return $this->standardPrintPhpDocChildNode($phpDocChildNode);
        }
        /** @var StartAndEnd|null $startAndEnd */
        $startAndEnd = $phpDocChildNode->getAttribute(PhpDocAttributeKey::START_AND_END);
        if ($startAndEnd instanceof StartAndEnd && !$shouldReprintChildNode) {
            $isLastToken = $nodeCount === $key;
            // correct previously changed node
            $this->correctPreviouslyReprintedFirstNode($key, $startAndEnd);
            $output = $this->addTokensFromTo($output, $this->currentTokenPosition, $startAndEnd->getEnd(), $isLastToken);
            $this->currentTokenPosition = $startAndEnd->getEnd();
            return rtrim($output);
        }
        if ($startAndEnd instanceof StartAndEnd) {
            $this->currentTokenPosition = $startAndEnd->getEnd();
        }
        $standardPrintedPhpDocChildNode = $this->standardPrintPhpDocChildNode($phpDocChildNode);
        return $output . $standardPrintedPhpDocChildNode;
    }
    private function printEnd(string $output): string
    {
        $lastTokenPosition = $this->getCurrentPhpDocInfo()->getPhpDocNode()->getAttribute(PhpDocAttributeKey::LAST_PHP_DOC_TOKEN_POSITION);
        $lastTokenPosition ??= $this->currentTokenPosition;
        if ($lastTokenPosition === 0) {
            return $output . "\n */";
        }
        return $this->addTokensFromTo($output, $lastTokenPosition, $this->tokenCount, \true);
    }
    private function addTokensFromTo(string $output, int $from, int $to, bool $shouldSkipEmptyLinesAbove): string
    {
        // skip removed nodes
        $positionJumpSet = [];
        $removedStartAndEnds = $this->removeNodesStartAndEndResolver->resolve($this->getCurrentPhpDocInfo()->getOriginalPhpDocNode(), $this->getCurrentPhpDocInfo()->getPhpDocNode(), $this->tokens);
        foreach ($removedStartAndEnds as $removedStartAndEnd) {
            $positionJumpSet[$removedStartAndEnd->getStart()] = $removedStartAndEnd->getEnd();
        }
        // include also space before, in case of inlined docs
        if (isset($this->tokens[$from - 1]) && $this->tokens[$from - 1][1] === Lexer::TOKEN_HORIZONTAL_WS) {
            --$from;
        }
        // skip extra empty lines above if this is the last one
        if ($shouldSkipEmptyLinesAbove && str_contains((string) $this->tokens[$from][0], "\n") && str_contains((string) $this->tokens[$from + 1][0], "\n")) {
            ++$from;
        }
        return $this->appendToOutput($output, $from, $to, $positionJumpSet);
    }
    /**
     * @param array<int, int> $positionJumpSet
     */
    private function appendToOutput(string $output, int $from, int $to, array $positionJumpSet): string
    {
        for ($i = $from; $i < $to; ++$i) {
            while (isset($positionJumpSet[$i])) {
                $i = $positionJumpSet[$i];
            }
            $output .= $this->tokens[$i][0] ?? '';
        }
        return $output;
    }
    private function correctPreviouslyReprintedFirstNode(int $key, StartAndEnd $startAndEnd): void
    {
        if ($this->currentTokenPosition !== 0) {
            return;
        }
        if ($key === 1) {
            return;
        }
        $startTokenPosition = $startAndEnd->getStart();
        $tokens = $this->getCurrentPhpDocInfo()->getTokens();
        if (!isset($tokens[$startTokenPosition - 1])) {
            return;
        }
        $previousToken = $tokens[$startTokenPosition - 1];
        if ($previousToken[1] === Lexer::TOKEN_PHPDOC_EOL) {
            --$startTokenPosition;
        }
        $this->currentTokenPosition = $startTokenPosition;
    }
    private function shouldReprint(PhpDocChildNode $phpDocChildNode): bool
    {
        $this->changedPhpDocNodeTraverser->traverse($phpDocChildNode);
        return $this->changedPhpDocNodeVisitor->hasChanged();
    }
    private function standardPrintPhpDocChildNode(PhpDocChildNode $phpDocChildNode): string
    {
        $printedNode = (string) $phpDocChildNode;
        if ($this->getCurrentPhpDocInfo()->isSingleLine()) {
            return ' ' . $printedNode;
        }
        return self::NEWLINE_WITH_ASTERISK . ($printedNode === '' ? '' : ' ' . $printedNode);
    }
}
