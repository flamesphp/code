<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\Php\PhpVersionProvider;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\KnownMagicClassMethodTypeRectorTest
 *
 * @see https://www.php.net/manual/en/language.oop5.overloading.php#object.call
 */
final class KnownMagicClassMethodTypeRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly PhpVersionProvider $phpVersionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add known magic methods parameter and return type declarations', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function __call($method, $args)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function __call(string $method, array $args)
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if (!$classMethod->isMagic()) {
                continue;
            }
            if ($this->parentClassMethodTypeOverrideGuard->hasParentClassMethod($classMethod)) {
                continue;
            }
            if ($this->isNames($classMethod, [MethodName::CALL, MethodName::CALL_STATIC])) {
                $secondParam = $classMethod->getParams()[1];
                if (!$secondParam->type instanceof Node) {
                    $secondParam->type = new Name('array');
                    $hasChanged = \true;
                }
            }
            // first arg string
            if ($this->isNames($classMethod, [MethodName::CALL, MethodName::CALL_STATIC, MethodName::__SET, MethodName::__GET, MethodName::ISSET, MethodName::UNSET])) {
                $firstParam = $classMethod->getParams()[0];
                if (!$firstParam->type instanceof Node) {
                    $firstParam->type = new Identifier('string');
                    $hasChanged = \true;
                }
            }
            if ($this->isName($classMethod, MethodName::__GET) && $this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::MIXED_TYPE) && !$classMethod->returnType instanceof Node) {
                $classMethod->returnType = new Identifier('mixed');
                $hasChanged = \true;
            }
            if ($this->isName($classMethod, MethodName::__SET) && $this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::MIXED_TYPE)) {
                $secondParam = $classMethod->getParams()[1];
                if (!$secondParam->type instanceof Node) {
                    $secondParam->type = new Identifier('mixed');
                    $hasChanged = \true;
                }
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SCALAR_TYPES;
    }
}
