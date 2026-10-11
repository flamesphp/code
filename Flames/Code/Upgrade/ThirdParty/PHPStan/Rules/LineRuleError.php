<?php

declare (strict_types=1);
namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface LineRuleError extends \PHPStan\Rules\RuleError
{
    public function getLine(): int;
}
