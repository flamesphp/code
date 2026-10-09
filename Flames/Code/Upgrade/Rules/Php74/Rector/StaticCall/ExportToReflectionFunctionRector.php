<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Rector\StaticCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Cast\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php74\Rector\StaticCall\ExportToReflectionFunctionRectorTest
 */
final class ExportToReflectionFunctionRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::EXPORT_TO_REFLECTION_FUNCTION;
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change export() to ReflectionFunction alternatives', [new CodeSample(<<<'CODE_SAMPLE'
$reflectionFunction = ReflectionFunction::export('foo');
$reflectionFunctionAsString = ReflectionFunction::export('foo', true);
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$reflectionFunction = new ReflectionFunction('foo');
$reflectionFunctionAsString = (string) new ReflectionFunction('foo');
CODE_SAMPLE
)]);
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
        if (!$node->class instanceof Name) {
            return null;
        }
        if (!$this->isName($node->name, 'export')) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $callerType = $this->nodeTypeResolver->getType($node->class);
        if (!$callerType->isSuperTypeOf(new ObjectType('ReflectionFunction'))->yes()) {
            return null;
        }
        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Arg) {
            return null;
        }
        $new = new New_($node->class, [new Arg($firstArg->value)]);
        $secondArg = $node->getArgs()[1] ?? null;
        if (!$secondArg instanceof Arg) {
            return $new;
        }
        if ($this->valueResolver->isTrue($secondArg->value)) {
            return new String_($new);
        }
        return $new;
    }
}
