<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ArgsAnalyzer
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param Arg[] $args
     */
    public function hasNamedArg(array $args): bool
    {
        $found = array_any($args, fn($arg) => $arg->name instanceof Identifier);
        return $found;
    }
    /**
     * @param Arg[] $args
     */
    public function resolveArgPosition(array $args, string $name, int $defaultPosition): int
    {
        foreach ($args as $position => $arg) {
            if (!$arg->name instanceof Identifier) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($arg->name, $name)) {
                continue;
            }
            return $position;
        }
        return $defaultPosition;
    }
    /**
     * @param Arg[] $args
     */
    public function resolveFirstNamedArgPosition(array $args): ?int
    {
        $position = 0;
        foreach ($args as $arg) {
            if ($arg->name instanceof Identifier) {
                return $position;
            }
            ++$position;
        }
        return null;
    }
}
