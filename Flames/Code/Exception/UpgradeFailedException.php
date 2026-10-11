<?php

declare(strict_types=1);

namespace Flames\Code\Exception;

use Flames\Code\Upgrade\ValueObject\Error\SystemError;
use Flames\Code\Upgrade\ValueObject\ProcessResult;

final class UpgradeFailedException extends \RuntimeException
{
    /**
     * @param list<array{message: string, file: string|null, line: int|null, caused_by: string|null}> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public static function fromProcessResult(ProcessResult $processResult): self
    {
        $errors = array_map(
            static fn (SystemError $error): array => [
                'message' => $error->getMessage(),
                'file' => $error->getRelativeFilePath(),
                'line' => $error->getLine(),
                'caused_by' => $error->getRectorClass(),
            ],
            $processResult->getSystemErrors(),
        );

        return new self(self::formatMessage($errors), $errors);
    }

    /**
     * @return list<array{message: string, file: string|null, line: int|null, caused_by: string|null}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param list<array{message: string, file: string|null, line: int|null, caused_by: string|null}> $errors
     */
    private static function formatMessage(array $errors): string
    {
        if ($errors === []) {
            return 'Code upgrade failed.';
        }

        $lines = ['Code upgrade failed with the following errors:'];

        foreach ($errors as $error) {
            $location = $error['file'] ?? 'unknown';
            if ($error['line'] !== null) {
                $location .= ':' . $error['line'];
            }

            $lines[] = sprintf('- [%s] %s', $location, $error['message']);
        }

        return implode("\n", $lines);
    }
}
