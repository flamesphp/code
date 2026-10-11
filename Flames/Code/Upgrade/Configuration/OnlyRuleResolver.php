<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Configuration;

use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Exception\Configuration\RectorRuleNameAmbiguousException;
use Flames\Code\Upgrade\Exception\Configuration\RectorRuleNotFoundException;
use ReflectionClass;
/**
 * @see \Flames\Code\Upgrade\Tests\Configuration\OnlyRuleResolverTest
 */
final readonly class OnlyRuleResolver
{
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(private array $rectors)
    {
    }
    public function resolve(string $rule): string
    {
        // fix wrongly double escaped backslashes
        $rule = str_replace('\\\\', '\\', $rule);
        // remove single quotes appearing when single-quoting arguments on windows
        if (str_starts_with($rule, "'") && str_ends_with($rule, "'")) {
            $rule = (string) substr($rule, 1, -1);
        }
        $rule = ltrim($rule, '\\');
        foreach ($this->rectors as $rector) {
            if ($rector::class === $rule) {
                return $rule;
            }
        }
        // allow short rule names if there are not duplicates
        $matching = [];
        foreach ($this->rectors as $rector) {
            if (str_ends_with($rector::class, '\\' . $rule)) {
                $matching[] = $rector::class;
            }
        }
        $matching = array_unique($matching);
        if (count($matching) === 1) {
            return $matching[0];
        }
        if (count($matching) > 1) {
            sort($matching);
            $message = sprintf('Short rule name "%s" is ambiguous. Specify the full rule name:' . \PHP_EOL . '- ' . implode(\PHP_EOL . '- ', $matching), $rule);
            throw new RectorRuleNameAmbiguousException($message);
        }
        if (!str_contains($rule, '\\')) {
            // the shell has eaten unescaped backslashes, e.g. --only=\Flames\Code\Upgrade\Some\Rule
            $flattenMatching = [];
            foreach ($this->rectors as $rector) {
                if (str_replace('\\', '', $rector::class) === $rule) {
                    $flattenMatching[] = $rector::class;
                }
            }
            $flattenMatching = array_unique($flattenMatching);
            if (count($flattenMatching) === 1) {
                return $flattenMatching[0];
            }
            $message = sprintf('Rule "%s" was not found.%sThe rule has no namespace. Make sure to escape the backslashes, and add quotes around the rule name: --only="My\Flames\Code\Upgrade\Rule"', $rule, \PHP_EOL);
        } else {
            // the rule class exists, it is just missing in the config
            if ($this->isRectorRuleClass($rule)) {
                throw new RectorRuleNotFoundException($this->createUnregisteredMessage($rule));
            }
            $message = sprintf('Rule "%s" was not found.%sMake sure it is registered in your config or in one of the sets', $rule, \PHP_EOL);
        }
        throw new RectorRuleNotFoundException($message);
    }
    /**
     * Is this an existing rule class, that is just not registered in the config?
     */
    private function isRectorRuleClass(string $className): bool
    {
        if (!class_exists($className)) {
            return \false;
        }
        $reflectionClass = new ReflectionClass($className);
        if ($reflectionClass->isAbstract()) {
            return \false;
        }
        return $reflectionClass->implementsInterface(RectorInterface::class);
    }
    private function createUnregisteredMessage(string $ruleClass): string
    {
        $shortRuleClass = (string) substr((string) strrchr($ruleClass, '\\'), 1);
        return sprintf('Rule "%s" exists, but is not registered in your Upgrade config.%sRegister it in your code-upgrade.php:' . \PHP_EOL . \PHP_EOL . '    ->withRules([%s::class])', $ruleClass, \PHP_EOL, $shortRuleClass);
    }
}
