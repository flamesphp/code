<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\FamilyTree\Reflection;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class FamilyRelationsAnalyzer
{
    public function __construct(private ReflectionProvider $reflectionProvider, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @api
     * @return string[]
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Interface_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name $classOrName
     */
    public function getClassLikeAncestorNames($classOrName): array
    {
        $ancestorNames = [];
        if ($classOrName instanceof Name) {
            $fullName = $this->nodeNameResolver->getName($classOrName);
            if (!$this->reflectionProvider->hasClass($fullName)) {
                return [];
            }
            $classReflection = $this->reflectionProvider->getClass($fullName);
            $ancestors = array_merge($classReflection->getParents(), $classReflection->getInterfaces());
            return array_map(static fn(ClassReflection $classReflection): string => $classReflection->getName(), $ancestors);
        }
        if ($classOrName instanceof Interface_) {
            foreach ($classOrName->extends as $extendInterfaceName) {
                $ancestorNames[] = $this->nodeNameResolver->getName($extendInterfaceName);
                $ancestorNames = array_merge($ancestorNames, $this->getClassLikeAncestorNames($extendInterfaceName));
            }
        }
        if ($classOrName instanceof Class_) {
            if ($classOrName->extends instanceof Name) {
                $ancestorNames[] = $this->nodeNameResolver->getName($classOrName->extends);
                $ancestorNames = array_merge($ancestorNames, $this->getClassLikeAncestorNames($classOrName->extends));
            }
            foreach ($classOrName->implements as $implement) {
                $ancestorNames[] = $this->nodeNameResolver->getName($implement);
                $ancestorNames = array_merge($ancestorNames, $this->getClassLikeAncestorNames($implement));
            }
        }
        /** @var string[] $ancestorNames */
        return $ancestorNames;
    }
}
