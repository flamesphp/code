<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/CurlSetOptArrayArgVisitor.cpp')]
final class CurlSetOptArrayArgVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE_NAME = 'isCurlSetOptArrayArg';
    #[Override]
    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
            $functionName = strtolower($node->name->name);
            if ($functionName === 'curl_setopt_array') {
                $args = $node->getArgs();
                if (isset($args[1])) {
                    $args[1]->setAttribute(self::ATTRIBUTE_NAME, \true);
                }
            }
        }
        return null;
    }
}
