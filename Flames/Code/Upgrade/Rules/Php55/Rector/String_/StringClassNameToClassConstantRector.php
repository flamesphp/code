<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php55\Rector\String_;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Php55\Rector\String_\StringClassNameToClassConstantRectorTest
 */
final class StringClassNameToClassConstantRector extends AbstractRector implements MinPhpVersionInterface, ConfigurableRectorInterface
{
    /**
     * @var string[]
     */
    private array $classesToSkip = [];
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace string class names by `<class>::class` constant', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
class AnotherClass
{
}

class SomeClass
{
    public function run()
    {
        return 'AnotherClass';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class AnotherClass
{
}

class SomeClass
{
    public function run()
    {
        return \AnotherClass::class;
    }
}
CODE_SAMPLE
, ['ClassName', 'AnotherClassName'])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [String_::class];
    }
    /**
     * @param String_ $node
     */
    public function refactor(Node $node): ?\PhpParser\Node\Expr\ClassConstFetch
    {
        if ($this->shouldSkipIsA($node)) {
            return null;
        }
        $classLikeName = $node->value;
        // remove leading slash
        $classLikeName = ltrim($classLikeName, '\\');
        if ($classLikeName === '') {
            return null;
        }
        if ($this->shouldSkip($classLikeName)) {
            return null;
        }
        $fullyQualified = new FullyQualified($classLikeName);
        return new ClassConstFetch($fullyQualified, 'class');
    }
    /**
     * @param string[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allString($configuration);
        $this->classesToSkip = $configuration;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::CLASSNAME_CONSTANT;
    }
    private function shouldSkip(string $classLikeName): bool
    {
        // skip short class names, mostly invalid use of strings
        if (!str_contains($classLikeName, '\\')) {
            return \true;
        }
        // possibly string
        if (ctype_lower($classLikeName[0])) {
            return \true;
        }
        if (!$this->reflectionProvider->hasClass($classLikeName)) {
            return \true;
        }
        foreach ($this->classesToSkip as $classToSkip) {
            if (str_contains($classToSkip, '*')) {
                if (fnmatch($classToSkip, $classLikeName, \FNM_NOESCAPE)) {
                    return \true;
                }
                continue;
            }
            if ($this->nodeNameResolver->isStringName($classLikeName, $classToSkip)) {
                return \true;
            }
        }
        return \false;
    }
    private function shouldSkipIsA(String_ $string): bool
    {
        if (!$string->getAttribute(AttributeKey::IS_ARG_VALUE, \false)) {
            return \false;
        }
        $funcCallName = $string->getAttribute(AttributeKey::FROM_FUNC_CALL_NAME);
        return $funcCallName === 'is_a';
    }
}
