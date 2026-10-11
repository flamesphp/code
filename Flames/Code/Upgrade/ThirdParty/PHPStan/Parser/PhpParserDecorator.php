<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use Override;
use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Parser;
use PHPStan\ShouldNotHappenException;
use function sprintf;
final class PhpParserDecorator implements Parser
{
    private \PHPStan\Parser\Parser $wrappedParser;
    public function __construct(\PHPStan\Parser\Parser $wrappedParser)
    {
        $this->wrappedParser = $wrappedParser;
    }
    /**
     * @return Node\Stmt[]
     */
    #[Override]
    public function parse(string $code, ?ErrorHandler $errorHandler = null): array
    {
        try {
            return $this->wrappedParser->parseString($code);
        } catch (\PHPStan\Parser\ParserErrorsException $e) {
            $message = $e->getMessage();
            if ($e->parsedFile !== null) {
                $message .= sprintf(' in file %s', $e->parsedFile);
            }
            throw new Error($message, $e->attributes);
        }
    }
    #[Override]
    public function getTokens(): array
    {
        throw new ShouldNotHappenException('PhpParserDecorator::getTokens() should not be called');
    }
}
