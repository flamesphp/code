<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp72\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\DowngradePhp72\NodeAnalyzer\BuiltInMethodAnalyzer;
use Flames\Code\Upgrade\DowngradePhp72\NodeAnalyzer\OverrideFromAnonymousClassMethodAnalyzer;
use Flames\Code\Upgrade\DowngradePhp72\NodeAnalyzer\SealedClassAnalyzer;
use Flames\Code\Upgrade\DowngradePhp72\PhpDoc\NativeParamToPhpDocDecorator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\AutowiredClassMethodOrPropertyAnalyzer;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @changelog https://www.php.net/manual/en/migration72.new-features.php#migration72.new-features.param-type-widening
 * @changelog https://3v4l.org/fOgSE
 *
 * @see \Flames\Code\Upgrade\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRectorTest
 */
final class DowngradeParameterTypeWideningRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<string, string[]>
     */
    private array $unsafeTypesToMethods = [];
    public function __construct(private readonly NativeParamToPhpDocDecorator $nativeParamToPhpDocDecorator, private readonly ReflectionResolver $reflectionResolver, private readonly AutowiredClassMethodOrPropertyAnalyzer $autowiredClassMethodOrPropertyAnalyzer, private readonly BuiltInMethodAnalyzer $builtInMethodAnalyzer, private readonly OverrideFromAnonymousClassMethodAnalyzer $overrideFromAnonymousClassMethodAnalyzer, private readonly SealedClassAnalyzer $sealedClassAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change param type to match the lowest type in whole family tree', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
interface SomeInterface
{
    public function test(array $input);
}
final class SomeClass implements SomeInterface
{
    public function test($input)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
interface SomeInterface
{
    /**
     * @param mixed[] $input
     */
    public function test($input);
}
final class SomeClass implements SomeInterface
{
    public function test($input)
    {
    }
}
CODE_SAMPLE
, ['ContainerInterface' => ['set', 'get', 'has', 'initialized'], 'SomeContainerInterface' => ['set', 'has']])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassLike::class];
    }
    /**
     * @param ClassLike $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getMethods() as $method) {
            $ancestorOverridableAnonymousClass = $this->overrideFromAnonymousClassMethodAnalyzer->matchAncestorClassReflectionOverridable($node, $method);
            if ($ancestorOverridableAnonymousClass instanceof ClassReflection) {
                $classMethod = $this->processRemoveParamTypeFromMethod($ancestorOverridableAnonymousClass, $method);
                if ($classMethod instanceof ClassMethod) {
                    $hasChanged = \true;
                    continue;
                }
                continue;
            }
            $classReflection = $this->reflectionResolver->resolveClassAndAnonymousClass($node);
            $classMethod = $this->processRemoveParamTypeFromMethod($classReflection, $method);
            if ($classMethod instanceof ClassMethod) {
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        $unsafeTypesToMethods = $configuration;
        foreach ($unsafeTypesToMethods as $key => $value) {
            Assert::string($key);
            Assert::isArray($value);
            Assert::allString($value);
        }
        $this->unsafeTypesToMethods = $unsafeTypesToMethods;
    }
    private function shouldSkip(ClassReflection $classReflection, ClassMethod $classMethod): bool
    {
        if ($classMethod->params === []) {
            return \true;
        }
        if ($classMethod->isPrivate()) {
            return \true;
        }
        if ($classMethod->isMagic()) {
            return \true;
        }
        if ($this->sealedClassAnalyzer->isSealedClass($classReflection)) {
            return \true;
        }
        if ($this->autowiredClassMethodOrPropertyAnalyzer->detect($classMethod)) {
            return \true;
        }
        if ($this->hasParamAlreadyNonTyped($classMethod)) {
            return \true;
        }
        return $this->isSafeType($classReflection, $classMethod);
    }
    private function processRemoveParamTypeFromMethod(ClassReflection $classReflection, ClassMethod $classMethod): ?ClassMethod
    {
        if ($this->shouldSkip($classReflection, $classMethod)) {
            return null;
        }
        if ($this->builtInMethodAnalyzer->isImplementsBuiltInInterface($classReflection, $classMethod)) {
            return null;
        }
        // Downgrade every scalar parameter, just to be sure
        foreach (array_keys($classMethod->params) as $paramPosition) {
            $this->removeParamTypeFromMethod($classMethod, $paramPosition);
        }
        return $classMethod;
    }
    private function removeParamTypeFromMethod(ClassMethod $classMethod, int $paramPosition): void
    {
        $param = $classMethod->params[$paramPosition] ?? null;
        if (!$param instanceof Param) {
            return;
        }
        // Add the current type in the PHPDoc
        $this->nativeParamToPhpDocDecorator->decorate($classMethod, $param);
        $param->type = null;
    }
    private function hasParamAlreadyNonTyped(ClassMethod $classMethod): bool
    {
        $found = array_all($classMethod->params, fn($param) => !$param->type instanceof Node);
        return $found;
    }
    private function isSafeType(ClassReflection $classReflection, ClassMethod $classMethod): bool
    {
        if ($this->unsafeTypesToMethods === []) {
            return \false;
        }
        $classReflectionName = $classReflection->getName();
        foreach ($this->unsafeTypesToMethods as $unsafeType => $unsafeMethods) {
            if (!$this->isNames($classMethod, $unsafeMethods)) {
                continue;
            }
            if ($classReflection->is($unsafeType)) {
                return \false;
            }
            // skip self too
            if ($classReflectionName === $unsafeType) {
                return \false;
            }
        }
        return \true;
    }
}
