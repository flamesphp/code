<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Naming;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\GroupUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
final readonly class AliasNameResolver
{
    public function __construct(private \Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver $useImportsResolver)
    {
    }
    /**
     * @param array<Use_|GroupUse> $uses
     */
    public function resolveByName(FullyQualified $fullyQualified, array $uses): ?string
    {
        $nameString = $fullyQualified->toString();
        foreach ($uses as $use) {
            $prefix = $this->useImportsResolver->resolvePrefix($use);
            foreach ($use->uses as $useUse) {
                if (!$useUse->alias instanceof Identifier) {
                    continue;
                }
                $fullyQualified = $prefix . $useUse->name->toString();
                if ($fullyQualified !== $nameString) {
                    continue;
                }
                return (string) $useUse->getAlias();
            }
        }
        return null;
    }
}
