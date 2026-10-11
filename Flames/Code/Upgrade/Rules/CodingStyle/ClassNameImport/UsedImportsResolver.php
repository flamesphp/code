<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\UseItem;
use Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\ValueObject\UsedImports;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final readonly class UsedImportsResolver
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private \Flames\Code\Upgrade\Rules\CodingStyle\ClassNameImport\UseImportsTraverser $useImportsTraverser, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @param Stmt[] $stmts
     */
    public function resolveForStmts(array $stmts): UsedImports
    {
        $useImports = [];
        /** @var Class_|null $class */
        $class = $this->betterNodeFinder->findFirstInstanceOf($stmts, Class_::class);
        // add class itself
        // is not anonymous class
        if ($class instanceof Class_) {
            $className = (string) $this->nodeNameResolver->getName($class);
            $useImports[] = new FullyQualifiedObjectType($className);
        }
        $usedConstImports = [];
        $usedFunctionImports = [];
        /** @param Use_::TYPE_* $useType */
        $this->useImportsTraverser->traverserStmts($stmts, static function (int $useType, UseItem $useItem, string $name) use (&$useImports, &$usedFunctionImports, &$usedConstImports): void {
            if ($useType === Use_::TYPE_NORMAL) {
                if ($useItem->alias instanceof Identifier) {
                    $useImports[] = new AliasedObjectType($useItem->alias->toString(), $name);
                } else {
                    $useImports[] = new FullyQualifiedObjectType($name);
                }
            }
            if ($useType === Use_::TYPE_FUNCTION) {
                $usedFunctionImports[] = new FullyQualifiedObjectType($name);
            }
            if ($useType === Use_::TYPE_CONSTANT) {
                $usedConstImports[] = new FullyQualifiedObjectType($name);
            }
        });
        return new UsedImports($useImports, $usedFunctionImports, $usedConstImports);
    }
}
