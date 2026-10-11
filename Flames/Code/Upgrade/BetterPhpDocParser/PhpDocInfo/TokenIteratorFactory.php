<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Lexer\Lexer;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Parser\TokenIterator;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Parser\BetterTokenIterator;
final readonly class TokenIteratorFactory
{
    public function __construct(private Lexer $lexer)
    {
    }
    public function create(string $content): BetterTokenIterator
    {
        $tokens = $this->lexer->tokenize($content);
        return new BetterTokenIterator($tokens);
    }
    public function createFromTokenIterator(TokenIterator $tokenIterator): BetterTokenIterator
    {
        if ($tokenIterator instanceof BetterTokenIterator) {
            return $tokenIterator;
        }
        // keep original tokens and index position
        $tokens = $tokenIterator->getTokens();
        $currentIndex = $tokenIterator->currentTokenIndex();
        return new BetterTokenIterator($tokens, $currentIndex);
    }
}
