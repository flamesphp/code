<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Analyser\Error;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\ShouldNotHappenException;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class TransformedRuleError implements IdentifierRuleError
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Error $error;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Error $error
    )
    {
        $this->error = $error;
    }
    public function getError(): Error
    {
        return $this->error;
    }
    public function getIdentifier(): string
    {
        $identifier = $this->error->identifier;
        if ($identifier === null) {
            throw new ShouldNotHappenException();
        }
        return $identifier;
    }
    public function getMessage(): string
    {
        return $this->error->message;
    }
}
