<?php

declare (strict_types=1);
namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface MetadataRuleError extends \PHPStan\Rules\RuleError
{
    /**
     * @return mixed[]
     */
    public function getMetadata(): array;
}
