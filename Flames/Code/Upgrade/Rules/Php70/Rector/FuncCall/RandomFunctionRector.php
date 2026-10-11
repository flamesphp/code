<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php70\Rector\FuncCall\RandomFunctionRectorTest
 */
final class RandomFunctionRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var array<string, string>
     */
    private const array OLD_TO_NEW_FUNCTION_NAMES = ['getrandmax' => 'mt_getrandmax', 'srand' => 'mt_srand', 'rand' => 'random_int'];
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes rand, srand, and getrandmax to newer alternatives', [new CodeSample('rand();', 'random_int();')]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }
        foreach (self::OLD_TO_NEW_FUNCTION_NAMES as $oldFunctionName => $newFunctionName) {
            if ($this->isName($node, $oldFunctionName)) {
                $node->name = new Name($newFunctionName);
                // special case: random_int(); → random_int(0, getrandmax());
                if ($newFunctionName === 'random_int') {
                    $args = $node->getArgs();
                    if ($args === []) {
                        $node->args[0] = new Arg(new Int_(0));
                        $node->args[1] = new Arg($this->nodeFactory->createFuncCall('mt_getrandmax'));
                    } elseif (count($args) === 2) {
                        $minValue = $this->valueResolver->getValue($args[0]->value);
                        $maxValue = $this->valueResolver->getValue($args[1]->value);
                        if (is_int($minValue) && is_int($maxValue) && $minValue > $maxValue) {
                            $temp = $node->args[0];
                            $node->args[0] = $node->args[1];
                            $node->args[1] = $temp;
                        }
                    }
                }
                return $node;
            }
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::CSPRNG_FUNCTIONS;
    }
}
