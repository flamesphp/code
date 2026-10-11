<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php81\Rector\New_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php81\Rector\New_\MyCLabsConstructorCallToEnumFromRectorTest
 */
final class MyCLabsConstructorCallToEnumFromRector extends AbstractRector implements MinPhpVersionInterface
{
    private const string MY_C_LABS_CLASS = 'MyCLabs\Enum\Enum';
    private const string DEFAULT_ENUM_CONSTRUCTOR = 'from';
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [New_::class];
    }
    /**
     * @param New_ $node
     */
    public function refactor(Node $node): ?Node
    {
        return $this->refactorConstructorCallToStaticFromCall($node);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ENUM;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Refactor MyCLabs Enum using constructor for instantiation', [new CodeSample(<<<'CODE_SAMPLE'
$enum = new Enum($args);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$enum = Enum::from($args);
CODE_SAMPLE
)]);
    }
    private function refactorConstructorCallToStaticFromCall(New_ $new): ?StaticCall
    {
        if (!$this->isObjectType($new->class, new ObjectType(self::MY_C_LABS_CLASS))) {
            return null;
        }
        $classname = $this->getName($new->class);
        if (in_array($classname, [ObjectReference::SELF, ObjectReference::STATIC], \true)) {
            $classname = ($nullsafeVariable1 = ScopeFetcher::fetch($new)->getClassReflection()) ? $nullsafeVariable1->getName() : null;
        }
        if ($classname === null) {
            return null;
        }
        if (!$this->isMyCLabsConstructor($new, $classname)) {
            return null;
        }
        return new StaticCall(new FullyQualified($classname), self::DEFAULT_ENUM_CONSTRUCTOR, $new->args);
    }
    private function isMyCLabsConstructor(New_ $new, string $classname): bool
    {
        $classReflection = $this->reflectionProvider->getClass($classname);
        if (!$classReflection->hasMethod(MethodName::CONSTRUCT)) {
            return \true;
        }
        return $classReflection->getMethod(MethodName::CONSTRUCT, ScopeFetcher::fetch($new))->getDeclaringClass()->getName() === self::MY_C_LABS_CLASS;
    }
}
