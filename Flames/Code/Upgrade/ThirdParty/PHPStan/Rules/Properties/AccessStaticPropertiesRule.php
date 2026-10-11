<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
#[RegisteredRule(0)]
final class AccessStaticPropertiesRule implements Rule
{
    private \PHPStan\Rules\Properties\AccessStaticPropertiesCheck $check;
    public function __construct(\PHPStan\Rules\Properties\AccessStaticPropertiesCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return StaticPropertyFetch::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->check->check($node, $scope, \false);
    }
}
