<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;
final class IgnoredErrorHelperProcessedResult
{
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $notIgnoredErrors;
    /**
     * @var list<array{Error, mixed[]|string}>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $ignoredErrors;
    /**
     * @var list<string>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $otherIgnoreMessages;
    /**
     * @param list<Error> $notIgnoredErrors
     * @param list<array{Error, mixed[]|string}> $ignoredErrors
     * @param list<string> $otherIgnoreMessages
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $notIgnoredErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $ignoredErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $otherIgnoreMessages
    )
    {
        $this->notIgnoredErrors = $notIgnoredErrors;
        $this->ignoredErrors = $ignoredErrors;
        $this->otherIgnoreMessages = $otherIgnoreMessages;
    }
    /**
     * @return list<Error>
     */
    public function getNotIgnoredErrors(): array
    {
        return $this->notIgnoredErrors;
    }
    /**
     * @return list<array{Error, mixed[]|string}>
     */
    public function getIgnoredErrors(): array
    {
        return $this->ignoredErrors;
    }
    /**
     * @return list<string>
     */
    public function getOtherIgnoreMessages(): array
    {
        return $this->otherIgnoreMessages;
    }
}
