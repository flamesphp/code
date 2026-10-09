<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Transform\Rector\StaticCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\Contract\Rector\ConfigurableRectorInterface;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Transform\ValueObject\StaticCallToNew;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\ConfiguredCodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
/**
 * @see \Flames\Code\Upgrade\Rules\Transform\Rector\StaticCall\StaticCallToNewRectorTest
 */
final class StaticCallToNewRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var StaticCallToNew[]
     */
    private array $staticCallsToNews = [];
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change static call to new instance', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $dotenv = JsonResponse::create(['foo' => 'bar'], Response::HTTP_OK);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $dotenv = new JsonResponse(['foo' => 'bar'], Response::HTTP_OK);
    }
}
CODE_SAMPLE
, [new StaticCallToNew('JsonResponse', 'create')])]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }
    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->staticCallsToNews as $staticCallToNew) {
            if (!$this->isName($node->class, $staticCallToNew->getClass())) {
                continue;
            }
            if (!$this->isName($node->name, $staticCallToNew->getMethod())) {
                continue;
            }
            $class = $this->getName($node->class);
            if ($class === null) {
                continue;
            }
            return new New_(new FullyQualified($class), $node->args);
        }
        return null;
    }
    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, StaticCallToNew::class);
        $this->staticCallsToNews = $configuration;
    }
}
