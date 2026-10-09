<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\VariadicPlaceholder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersion;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRectorTest
 */
final class FunctionFirstClassCallableRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getRuleDefinition(): RuleDefinition
    {
        // see RFC https://wiki.php.net/rfc/first_class_callable_syntax
        return new RuleDefinition('Upgrade string callback functions to first class callable', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(array $data)
    {
        return array_map('trim', $data);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run(array $data)
    {
        return array_map(trim(...), $data);
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
        return [FuncCall::class];
    }
    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): ?FuncCall
    {
        if (!$node->name instanceof Name) {
            return null;
        }
        if ($node->isFirstClassCallable()) {
            return null;
        }
        $functionName = (string) $this->getName($node);
        try {
            $reflectionFunction = new ReflectionFunction($functionName);
        } catch (ReflectionException) {
            return null;
        }
        $callableArgs = [];
        foreach ($reflectionFunction->getParameters() as $reflectionParameter) {
            if ($reflectionParameter->getType() instanceof ReflectionNamedType && $reflectionParameter->getType()->getName() === 'callable') {
                $callableArgs[] = $reflectionParameter->getPosition();
            }
        }
        $hasChanged = \false;
        foreach ($node->getArgs() as $key => $arg) {
            if (!in_array($key, $callableArgs, \true)) {
                continue;
            }
            if (!$arg->value instanceof String_) {
                continue;
            }
            $isFullyQualified = str_starts_with($arg->value->value, '\\');
            $callableName = ltrim($arg->value->value, '\\');
            $node->args[$key] = new Arg(new FuncCall($isFullyQualified || str_contains($callableName, '\\') ? new FullyQualified($callableName) : new Name($callableName), [new VariadicPlaceholder()]), \false, \false, [], $arg->name);
            $hasChanged = \true;
        }
        return $hasChanged ? $node : null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_81;
    }
}
