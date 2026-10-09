<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Include_;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Concat;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Include_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\MagicConst\Dir;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Util\StringUtils;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRectorTest
 */
final class AbsolutizeRequireAndIncludePathRector extends AbstractRector
{
    /**
     * @see https://regex101.com/r/N8oLqv/1
     */
    private const string WINDOWS_DRIVE_REGEX = '#^[a-zA-z]\:[\/\\\\]#';
    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Migrate include/require to absolute path. This Upgrade might introduce backwards incompatible code, when the include/require being changed depends on the current working directory.', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        require 'autoload.php';

        require $variable;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        require __DIR__ . '/autoload.php';

        require $variable;
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
        return [Include_::class];
    }
    /**
     * @param Include_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->expr instanceof Concat && $node->expr->left instanceof String_ && $this->isRefactorableStringPath($node->expr->left)) {
            $node->expr->left = $this->prefixWithDirConstant($node->expr->left);
            return $node;
        }
        if (!$node->expr instanceof String_) {
            return null;
        }
        if (!$this->isRefactorableStringPath($node->expr)) {
            return null;
        }
        /** @var string $includeValue */
        $includeValue = $this->valueResolver->getValue($node->expr);
        // skip phar
        if (str_starts_with($includeValue, 'phar://')) {
            return null;
        }
        // skip absolute paths
        if (str_starts_with($includeValue, '/') || str_starts_with($includeValue, '\\')) {
            return null;
        }
        if (str_contains($includeValue, 'config/')) {
            return null;
        }
        if (StringUtils::isMatch($includeValue, self::WINDOWS_DRIVE_REGEX)) {
            return null;
        }
        // add preslash to string
        $node->expr->value = str_starts_with($includeValue, './') ? Strings::substring($includeValue, 1) : '/' . $includeValue;
        $node->expr = $this->prefixWithDirConstant($node->expr);
        return $node;
    }
    private function isRefactorableStringPath(String_ $string): bool
    {
        return !str_starts_with($string->value, 'phar://');
    }
    private function prefixWithDirConstant(String_ $string): Concat
    {
        $this->removeExtraDotSlash($string);
        $this->prependSlashIfMissing($string);
        return new Concat(new Dir(), $string);
    }
    /**
     * Remove "./" which would break the path
     */
    private function removeExtraDotSlash(String_ $string): void
    {
        if (!str_starts_with($string->value, './')) {
            return;
        }
        $string->value = Strings::replace($string->value, '#^\.\/#', '/');
    }
    private function prependSlashIfMissing(String_ $string): void
    {
        if (str_starts_with($string->value, '/')) {
            return;
        }
        $string->value = '/' . $string->value;
    }
}
