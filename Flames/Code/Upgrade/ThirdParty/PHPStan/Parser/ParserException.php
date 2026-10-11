<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\PHPStan\Parser;

use Exception;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Lexer\Lexer;
use function assert;
use function json_encode;
use function sprintf;
use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
class ParserException extends Exception
{
    public function __construct(private readonly string $currentTokenValue, private readonly int $currentTokenType, private readonly int $currentOffset, private readonly int $expectedTokenType, private readonly ?string $expectedTokenValue, private readonly ?int $currentTokenLine)
    {
        parent::__construct(sprintf('Unexpected token %s, expected %s%s at offset %d%s', $this->formatValue($this->currentTokenValue), Lexer::TOKEN_LABELS[$this->expectedTokenType], $this->expectedTokenValue !== null ? sprintf(' (%s)', $this->formatValue($this->expectedTokenValue)) : '', $this->currentOffset, $this->currentTokenLine === null ? '' : sprintf(' on line %d', $this->currentTokenLine)));
    }
    public function getCurrentTokenValue(): string
    {
        return $this->currentTokenValue;
    }
    public function getCurrentTokenType(): int
    {
        return $this->currentTokenType;
    }
    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }
    public function getExpectedTokenType(): int
    {
        return $this->expectedTokenType;
    }
    public function getExpectedTokenValue(): ?string
    {
        return $this->expectedTokenValue;
    }
    public function getCurrentTokenLine(): ?int
    {
        return $this->currentTokenLine;
    }
    private function formatValue(string $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        assert($json !== \false);
        return $json;
    }
}
