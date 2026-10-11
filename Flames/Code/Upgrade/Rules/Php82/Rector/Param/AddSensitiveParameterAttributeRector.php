<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php82\Rector\Param;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as it relies on a provided variable name list. Matching parameters by name is vague and risky, as the same name can hold a non-sensitive value. Add the #[\SensitiveParameter] attribute per case instead.
 */
final class AddSensitiveParameterAttributeRector extends AbstractRector implements ConfigurableRectorInterface, MinPhpVersionInterface, DeprecatedInterface
{
    /**
     * @api
     */
    public const string SENSITIVE_PARAMETERS = 'sensitive_parameters';
    /**
     * @param array<string, mixed> $configuration
     */
    public function configure(array $configuration): void
    {
    }
    public function getNodeTypes(): array
    {
        return [Param::class];
    }
    /**
     * @param Node\Param $node
     */
    public function refactor(Node $node): ?Param
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as matching sensitive parameters by name is vague and risky. Add the #[\SensitiveParameter] attribute per case instead', self::class));
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add SensitiveParameter attribute to method and function configured parameters', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run(string $password)
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run(#[\SensitiveParameter] string $password)
    {
    }
}
CODE_SAMPLE
, [self::SENSITIVE_PARAMETERS => ['password']])]);
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::SENSITIVE_PARAMETER_ATTRIBUTE;
    }
}
