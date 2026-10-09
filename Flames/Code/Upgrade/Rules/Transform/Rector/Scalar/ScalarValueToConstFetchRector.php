<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\Scalar;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\ScalarValueToConstFetch;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @deprecated This rule is deprecated, as the scalar value target is too wide. The very same value can be used in an unrelated context, e.g. an array key, a version number or a message, and get replaced by a constant that does not belong there. Use a custom rule scoped to the exact context instead.
 */
final class ScalarValueToConstFetchRector extends AbstractRector implements ConfigurableRectorInterface, DeprecatedInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces Scalar values with a ConstFetch or ClassConstFetch', [new ConfiguredCodeSample(<<<'SAMPLE'
$var = 10;
SAMPLE
, <<<'SAMPLE'
$var = \SomeClass::FOOBAR_INT;
SAMPLE
, [new ScalarValueToConstFetch(new Int_(10), new ClassConstFetch(new FullyQualified('SomeClass'), new Identifier('FOOBAR_INT')))])]);
    }
    public function getNodeTypes(): array
    {
        return [String_::class, Float_::class, Int_::class];
    }
    /**
     * @param String_|Float_|Int_ $node
     * @return \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ConstFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch|null
     */
    public function refactor(Node $node): never
    {
        throw new ShouldNotHappenException(sprintf('"%s" rule is deprecated, as the scalar value target is too wide and replaces values in unrelated contexts; use a custom rule scoped to the exact context instead', self::class));
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
    }
}
