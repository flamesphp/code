<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php84\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as the #[Deprecated] attribute triggers a runtime deprecation, while the @deprecated annotation is a static hint only. Those have a different purpose and are not interchangeable. Use "phpstan/phpstan-deprecation-rules" to report the annotation instead.
 */
final class DeprecatedAnnotationToDeprecatedAttributeRector extends AbstractRector implements MinPhpVersionInterface, DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change @deprecated annotation to Deprecated attribute', [new CodeSample(<<<'CODE_SAMPLE'
/**
 * @deprecated 1.0.0 Use SomeOtherFunction instead
 */
function someFunction()
{
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
#[\Deprecated(message: 'Use SomeOtherFunction instead', since: '1.0.0')]
function someFunction()
{
}
CODE_SAMPLE
)]);
    }
    public function getNodeTypes(): array
    {
        return [Function_::class, ClassMethod::class, ClassConst::class];
    }
    /**
     * @param ClassConst|Function_|ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as the #[Deprecated] attribute triggers a runtime deprecation, unlike the @deprecated annotation; use "phpstan/phpstan-deprecation-rules" to report the annotation instead', self::class));
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::DEPRECATED_ATTRIBUTE;
    }
}
